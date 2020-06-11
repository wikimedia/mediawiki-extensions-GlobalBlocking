<?php

/**
 * Static utility class of the GlobalBlocking extension.
 *
 * @license GPL-2.0-or-later
 */

use MediaWiki\Block\DatabaseBlock;
use MediaWiki\MediaWikiServices;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\IResultWrapper;

class GlobalBlocking {
	private const TYPE_IP = 2;
	private const TYPE_RANGE = 3;

	/**
	 * @param User $user
	 * @param string $ip
	 * @return DatabaseBlock|null
	 * @throws MWException
	 */
	public static function getUserBlock( $user, $ip ) {
		$details = static::getUserBlockDetails( $user, $ip );

		if ( !empty( $details['error'] ) ) {
			$row = $details['block'];
			$block = new GlobalBlock(
				$row,
				$details['error'],
				[
					'address' => $row->gb_address,
					'reason' => $row->gb_reason,
					'timestamp' => $row->gb_timestamp,
					'anonOnly' => $row->gb_anon_only,
					'expiry' => $row->gb_expiry,
				]
			);
			return $block;
		}

		return null;
	}

	/**
	 * @param User $user
	 * @param string $ip
	 * @return Message[] empty or message objects
	 * @throws MWException
	 */
	public static function getUserBlockErrors( $user, $ip ) {
		$details = static::getUserBlockDetails( $user, $ip );
		return $details['error'];
	}

	/**
	 * @param User $user
	 * @param string $ip
	 * @return array ['block' => DB row, 'error' => empty or message objects]
	 * @phan-return array{block:stdClass|null,error:Message[]}
	 * @throws MWException
	 */
	private static function getUserBlockDetails( $user, $ip ) {
		global $wgLang, $wgRequest, $wgGlobalBlockingBlockXFF;
		static $result = null;

		// Instance cache
		if ( $result !== null ) {
			return $result;
		}

		if ( $user->isAllowed( 'ipblock-exempt' ) || $user->isAllowed( 'globalblock-exempt' ) ) {
			// User is exempt from IP blocks.
			$result = [ 'block' => null, 'error' => [] ];
			return $result;
		}

		$block = self::getGlobalBlockingBlock( $ip, $user->isAnon() );
		if ( $block ) {
			// Check for local whitelisting
			if ( self::getWhitelistInfo( $block->gb_id ) ) {
				// Block has been whitelisted.
				$result = [ 'block' => null, 'error' => [] ];
				return $result;
			}

			$blockTimestamp = $wgLang->timeanddate( wfTimestamp( TS_MW, $block->gb_timestamp ), true );
			$blockExpiry = $wgLang->formatExpiry( $block->gb_expiry );
			$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
			$blockingUser = self::maybeLinkUserpage( $block->gb_by_wiki, $block->gb_by );
			if ( IPUtils::isValid( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked';
				$hookName = 'GlobalBlockingBlockedIpMsg';
			} elseif ( IPUtils::isValidRange( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked-range';
				$hookName = 'GlobalBlockingBlockedIpRangeMsg';
			} else {
				throw new MWException(
					"This should not happen. IP globally blocked is not valid and is not a valid range?"
				);
			}

			// Allow site customization of blocked message.
			Hooks::run( $hookName, [ &$errorMsg ] );
			$result = [
				'block' => $block,
				'error' => [
					wfMessage(
						$errorMsg,
						$blockingUser,
						$display_wiki,
						$block->gb_reason,
						$blockTimestamp,
						$blockExpiry,
						$ip,
						$block->gb_address
					)
				],
			];
			return $result;
		}

		if ( $wgGlobalBlockingBlockXFF ) {
			$xffIps = $wgRequest->getHeader( 'X-Forwarded-For' );
			if ( $xffIps ) {
				$xffIps = array_map( 'trim', explode( ',', $xffIps ) );
				$blocks = self::checkIpsForBlock( $xffIps, $user->isAnon() );
				if ( count( $blocks ) > 0 ) {
					$appliedBlock = self::getAppliedBlock( $xffIps, $blocks );
					if ( $appliedBlock !== null ) {
						list( $blockIP, $block ) = $appliedBlock;
						$blockTimestamp = $wgLang->timeanddate(
							wfTimestamp( TS_MW, $block->gb_timestamp ),
							true
						);
						$blockExpiry = $wgLang->formatExpiry( $block->gb_expiry );
						$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
						$blockingUser = self::maybeLinkUserpage( $block->gb_by_wiki, $block->gb_by );
						// Allow site customization of blocked message.
						$blockedIpXffMsg = 'globalblocking-ipblocked-xff';
						Hooks::run( 'GlobalBlockingBlockedIpXffMsg', [ &$blockedIpXffMsg ] );
						$result = [
							'block' => $block,
							'error' => [
								wfMessage(
									$blockedIpXffMsg,
									$blockingUser,
									$display_wiki,
									$block->gb_reason,
									$blockTimestamp,
									$blockExpiry,
									$blockIP
								)
							],
						];
						return $result;
					}
				}
			}
		}

		$result = [ 'block' => null, 'error' => [] ];
		return $result;
	}

	public static function getTargetType( $target ) {
		if ( IPUtils::isValid( $target ) ) {
			return self::TYPE_IP;
		} elseif ( IPUtils::isValidRange( $target ) ) {
			return self::TYPE_RANGE;
		}
	}

	/**
	 * Choose the most specific block from some combination of user, IP and IP range
	 * blocks. Decreasing order of specificity: IP > narrower IP range > wider IP
	 * range. A range that encompasses one IP address is ranked equally to a singe IP.
	 *
	 * Note that DatabaseBlock::chooseBlocks chooses blocks in a different way.
	 *
	 * This is based on DatabaseBlock::chooseMostSpecificBlock
	 *
	 * @param IResultWrapper $blocks These should not include autoblocks or ID blocks
	 * @return stdClass|null The block with the most specific target
	 */
	protected static function chooseMostSpecificBlock( $blocks ) {
		if ( $blocks->numRows() === 1 ) {
			return $blocks->current();
		}

		# This result could contain a block on the user, a block on the IP, and a russian-doll
		# set of rangeblocks.  We want to choose the most specific one, so keep a leader board.
		$bestBlock = null;

		# Lower will be better
		$bestBlockScore = 100;
		foreach ( $blocks as $block ) {
			$target = $block->gb_address;
			$type = self::getTargetType( $target );
			if ( $type == self::TYPE_RANGE ) {
				# This is the number of bits that are allowed to vary in the block, give
				# or take some floating point errors
				$max = IPUtils::isIPv6( $target ) ? 128 : 32;
				list( $network, $bits ) = IPUtils::parseCIDR( $target );
				$size = $max - $bits;

				# Rank a range block covering a single IP equally with a single-IP block
				$score = self::TYPE_RANGE - 1 + ( $size / $max );

			} else {
				$score = $type;
			}

			if ( $score < $bestBlockScore ) {
				$bestBlockScore = $score;
				$bestBlock = $block;
			}
		}

		return $bestBlock;
	}

	/**
	 * Get a block
	 * @param string $ip The IP address to be checked
	 * @param bool $anon Get anon blocks only
	 * @return stdClass|false The block, or false if none is found
	 */
	public static function getGlobalBlockingBlock( $ip, $anon ) {
		$dbr = self::getGlobalBlockingDatabase( DB_REPLICA );

		$conds = self::getRangeCondition( $ip );

		if ( !$anon ) {
			$conds['gb_anon_only'] = 0;
		}

		// Get the block
		$blocks = $dbr->select( 'globalblocks', self::selectFields(), $conds, __METHOD__ );
		return self::chooseMostSpecificBlock( $blocks );
	}

	/**
	 * Get a database range condition for an IP address
	 * @param string $ip The IP address or range
	 * @return string[] a SQL condition
	 */
	public static function getRangeCondition( $ip ) {
		$dbr = self::getGlobalBlockingDatabase( DB_REPLICA );

		list( $start, $end ) = IPUtils::parseRange( $ip );

		// Don't bother checking blocks out of this /16.
		// @todo Make the range limit configurable
		$ipPattern = substr( $start, 0, 4 );

		return [
			'gb_range_start ' . $dbr->buildLike( $ipPattern, $dbr->anyString() ),
			'gb_range_start <= ' . $dbr->addQuotes( $start ),
			'gb_range_end >= ' . $dbr->addQuotes( $end ),
			// @todo expiry shouldn't be in this function
			'gb_expiry > ' . $dbr->addQuotes( $dbr->timestamp( wfTimestampNow() ) )
		];
	}

	/**
	 * Check an array of IPs for a block on any
	 * @param string[] $ips The Array of IP addresses to be checked
	 * @param bool $anon Get anon blocks only
	 * @return stdClass[] Array of applicable blocks
	 */
	private static function checkIpsForBlock( $ips, $anon ) {
		$dbr = self::getGlobalBlockingDatabase( DB_REPLICA );
		$conds = [];
		foreach ( $ips as $ip ) {
			if ( IPUtils::isValid( $ip ) ) {
				$conds[] = $dbr->makeList( self::getRangeCondition( $ip ), LIST_AND );
			}
		}

		if ( !$conds ) {
			// No valid IPs provided so don't even make the query. Bug 59705
			return [];
		}
		$conds = [ $dbr->makeList( $conds, LIST_OR ) ];

		if ( !$anon ) {
			$conds['gb_anon_only'] = 0;
		}

		$blocks = [];
		$results = $dbr->select( 'globalblocks', self::selectFields(), $conds, __METHOD__ );
		if ( !$results ) {
			return [];
		}

		foreach ( $results as $block ) {
			if ( !self::getWhitelistInfo( $block->gb_id ) ) {
				$blocks[] = $block;
			}
		}

		return $blocks;
	}

	/**
	 * From a list of XFF ips, and list of blocks that apply, choose the block that will
	 * be shown to the end user. Using the first block in the array for now.
	 *
	 * @param string[] $ips The Array of IP addresses to be checked
	 * @param stdClass[] $blocks The Array of blocks (db rows)
	 * @return array|null ($ip, $block) the chosen ip and block
	 * @phan-return array{string,stdClass}|null
	 */
	private static function getAppliedBlock( $ips, $blocks ) {
		$block = array_shift( $blocks );
		foreach ( $ips as $ip ) {
			$ipHex = IPUtils::toHex( $ip );
			if ( $block->gb_range_start <= $ipHex && $block->gb_range_end >= $ipHex ) {
				return [ $ip, $block ];
			}
		}

		return null;
	}

	/**
	 * @param int $dbtype either DB_REPLICA or DB_MASTER
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	public static function getGlobalBlockingDatabase( $dbtype ) {
		global $wgGlobalBlockingDatabase;

		$factory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $factory->getMainLB( $wgGlobalBlockingDatabase );

		return $lb->getConnectionRef( $dbtype, 'globalblocking', $wgGlobalBlockingDatabase );
	}

	/**
	 * @param string $ip
	 * @param int $dbtype either DB_REPLICA or DB_MASTER
	 * @return int
	 */
	public static function getGlobalBlockId( $ip, $dbtype = DB_REPLICA ) {
		$db = self::getGlobalBlockingDatabase( $dbtype );

		$row = $db->selectRow( 'globalblocks', 'gb_id', [ 'gb_address' => $ip ], __METHOD__ );

		if ( !$row ) {
			return 0;
		}

		return (int)$row->gb_id;
	}

	/**
	 * Purge stale block rows.
	 *
	 * This is expensive. It involves opening a connection to a new master,
	 * and doing a write query. We should only do it when a connection to the master
	 * is already open (currently, when a global block is made).
	 *
	 * @throws DBUnexpectedError
	 */
	public static function purgeExpired() {
		$dbw = self::getGlobalBlockingDatabase( DB_MASTER );
		$dbw->delete(
			'globalblocks',
			[ 'gb_expiry<' . $dbw->addQuotes( $dbw->timestamp() ) ],
			__METHOD__
		);

		// Purge the global_block_whitelist table.
		// We can't be perfect about this without an expensive check on the master
		// for every single global block. However, we can be clever about it and store
		// the expiry of global blocks in the global_block_whitelist table.
		// That way, most blocks will fall out of the table naturally when they expire.
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'global_block_whitelist',
			[ 'gbw_expiry<' . $dbw->addQuotes( $dbw->timestamp() )
			],
			__METHOD__
		);
	}

	/**
	 * @param null|int $id
	 * @param null|string $address
	 * @return array|false
	 * @phan-return array{user:int,reason:string}|false
	 * @throws Exception
	 */
	public static function getWhitelistInfo( $id = null, $address = null ) {
		if ( $id != null ) {
			$conds = [ 'gbw_id' => $id ];
		} elseif ( $address != null ) {
			$conds = [ 'gbw_address' => $address ];
		} else {
			// WTF?
			throw new Exception( "Neither Block IP nor Block ID given for retrieving whitelist status" );
		}

		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			'global_block_whitelist',
			[ 'gbw_by', 'gbw_reason' ],
			$conds,
			__METHOD__
		);

		if ( $row == false ) {
			// Not whitelisted.
			return false;
		} else {
			// Block has been whitelisted
			return [ 'user' => $row->gbw_by, 'reason' => $row->gbw_reason ];
		}
	}

	/**
	 * @param string $block_ip
	 * @return array|bool
	 * @phan-return array{user:int,reason:string}|false
	 */
	public static function getWhitelistInfoByIP( $block_ip ) {
		return self::getWhitelistInfo( null, $block_ip );
	}

	/**
	 * @param string $wiki_id
	 * @param string $user
	 * @return string
	 */
	public static function maybeLinkUserpage( $wiki_id, $user ) {
		$wiki = WikiMap::getWiki( $wiki_id );

		if ( $wiki ) {
			return "[" . $wiki->getFullUrl( "User:$user" ) . " $user]";
		}
		return $user;
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string|bool $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return array[] Empty on success, array to create message objects on failure
	 */
	public static function insertBlock( $address, $reason, $expiry, $blocker, $options = [] ) {
		$errors = [];

		## Purge expired blocks.
		self::purgeExpired();

		## Validate input
		$ip = IPUtils::sanitizeIP( $address );

		$anonOnly = in_array( 'anon-only', $options );
		$modify = in_array( 'modify', $options );

		if ( !IPUtils::isIPAddress( $ip ) ) {
			// Invalid IP address.
			$errors[] = [ 'globalblocking-block-ipinvalid', $ip ];
		}

		if ( $expiry === false ) {
			$errors[] = [ 'globalblocking-block-expiryinvalid' ];
		}

		// Check for too-big ranges.
		list( $range_start, $range_end ) = IPUtils::parseRange( $ip );

		if ( substr( $range_start, 0, 4 ) != substr( $range_end, 0, 4 ) ) {
			// Range crosses a /16 boundary.
			$errors[] = [ 'globalblocking-block-bigrange', $ip ];
		}

		// Normalise the range
		if ( $range_start != $range_end ) {
			$ip = IPUtils::sanitizeRange( $ip );
		}

		// Check for an existing block in the master database
		$existingBlock = self::getGlobalBlockId( $ip, DB_MASTER );
		if ( !$modify && $existingBlock ) {
			$errors[] = [ 'globalblocking-block-alreadyblocked', $ip ];
		}

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		// We're a-ok.
		$dbw = self::getGlobalBlockingDatabase( DB_MASTER );

		$row = [
			'gb_address' => $ip,
			'gb_by' => $blocker->getName(),
			'gb_by_wiki' => wfWikiId(),
			'gb_reason' => $reason,
			'gb_timestamp' => $dbw->timestamp( wfTimestampNow() ),
			'gb_anon_only' => $anonOnly,
			'gb_expiry' => $dbw->encodeExpiry( $expiry ),
			'gb_range_start' => $range_start,
			'gb_range_end' => $range_end,
		];

		if ( $modify ) {
			$dbw->update( 'globalblocks', $row, [ 'gb_id' => $existingBlock ], __METHOD__ );
		} else {
			$dbw->insert( 'globalblocks', $row, __METHOD__, [ 'IGNORE' ] );
		}

		if ( !$dbw->affectedRows() ) {
			// Race condition?
			return [ [ 'globalblocking-block-failure', $ip ] ];
		}

		return [];
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return array[] Empty on success, array to create message objects on failure
	 */
	public static function block( $address, $reason, $expiry, $blocker, $options = [] ) {
		$expiry = SpecialBlock::parseExpiryInput( $expiry );
		$errors = self::insertBlock( $address, $reason, $expiry, $blocker, $options );

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		$anonOnly = in_array( 'anon-only', $options );
		$modify = in_array( 'modify', $options );

		// Log it.
		$logAction = $modify ? 'modify' : 'gblock2';
		$flags = [];

		if ( $anonOnly ) {
			$flags[] = wfMessage( 'globalblocking-list-anononly' )->inContentLanguage()->text();
		}

		if ( $expiry != 'infinity' ) {
			$contLang = MediaWikiServices::getInstance()->getContentLanguage();
			$displayExpiry = $contLang->timeanddate( $expiry );
			$flags[] = wfMessage( 'globalblocking-logentry-expiry', $displayExpiry )
				->inContentLanguage()->text();
		} else {
			$flags[] = wfMessage( 'globalblocking-logentry-noexpiry' )->inContentLanguage()->text();
		}

		$info = implode( ', ', $flags );

		$page = new LogPage( 'gblblock' );
		$page->addEntry( $logAction,
			Title::makeTitleSafe( NS_USER, $address ),
			$reason,
			[ $info, $address ],
			$blocker
		);

		return [];
	}

	/**
	 * Build links to other global blocking special pages, shown in the subtitle
	 * @param SpecialPage $sp SpecialPage instance for context
	 * @return string links to special pages
	 */
	public static function buildSubtitleLinks( SpecialPage $sp ) {
		// Add a few useful links
		$links = [];
		$pagetype = $sp->getName();
		$linkRenderer = $sp->getLinkRenderer();

		// Don't show a link to a special page on the special page itself.
		// Show the links only if the user has sufficient rights
		if ( $pagetype != 'GlobalBlockList' ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockList' );
			$links[] = $linkRenderer->makeKnownLink( $title, $sp->msg( 'globalblocklist' )->text() );
		}
		$canBlock = $sp->getUser()->isAllowed( 'globalblock' );
		if ( $pagetype != 'GlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlock' );
			$links[] = $linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-block' )->text() );
		}
		if ( $pagetype != 'RemoveGlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'RemoveGlobalBlock' );
			$links[] = $linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-unblock' )->text() );
		}
		if ( $pagetype != 'GlobalBlockStatus' && $sp->getUser()->isAllowed( 'globalblock-whitelist' ) ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockStatus' );
			$links[] = $linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-status' )->text() );
		}
		if ( $pagetype == 'GlobalBlock' && $sp->getUser()->isAllowed( 'editinterface' ) ) {
			$title = Title::makeTitle( NS_MEDIAWIKI, 'Globalblocking-block-reason-dropdown' );
			$links[] = $linkRenderer->makeKnownLink(
				$title,
				$sp->msg( 'globalblocking-block-edit-dropdown' )->text(),
				[],
				[ 'action' => 'edit' ]
			);
		}
		$linkItems = count( $links )
			? $sp->msg( 'parentheses', $sp->getLanguage()->pipeList( $links ) )->text()
			: '';
		return $linkItems;
	}

	public static function selectFields() {
		return [ 'gb_id', 'gb_address', 'gb_by', 'gb_by_wiki', 'gb_reason', 'gb_timestamp',
			'gb_anon_only', 'gb_expiry', 'gb_range_start', 'gb_range_end' ];
	}
}
