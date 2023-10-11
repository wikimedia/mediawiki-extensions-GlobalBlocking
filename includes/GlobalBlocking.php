<?php

namespace MediaWiki\Extension\GlobalBlocking;

use Exception;
use LogPage;
use MediaWiki\Block\BlockUser;
use MediaWiki\Extension\GlobalBlocking\Hook\GlobalBlockingHookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;
use Message;
use RequestContext;
use SpecialPage;
use StatusValue;
use stdClass;
use UnexpectedValueException;
use User;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Static utility class of the GlobalBlocking extension.
 *
 * @license GPL-2.0-or-later
 */
class GlobalBlocking {
	private const TYPE_IP = 2;
	private const TYPE_RANGE = 3;

	/**
	 * @param User $user
	 * @param string|null $ip
	 * @return GlobalBlock|null
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
					'xff' => $details['xff'] ?? false,
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
	 */
	public static function getUserBlockErrors( $user, $ip ) {
		$details = static::getUserBlockDetails( $user, $ip );
		return $details['error'];
	}

	/**
	 * @param User $user Note this may not be the session user.
	 * @param string|null $ip
	 * @return array ['block' => DB row, 'error' => empty or message objects]
	 * @phan-return array{block:stdClass|null,error:Message[]}
	 */
	private static function getUserBlockDetails( $user, $ip ) {
		// Instance cache
		static $cachedResults = [];
		$result = &$cachedResults[$user->getName()];

		if ( isset( $result ) ) {
			return $result;
		}

		$services = MediaWikiServices::getInstance();
		$statsdFactory = $services->getStatsdDataFactory();
		$statsdFactory->increment( 'global_blocking.get_user_block' );

		if ( $user->isAllowed( 'ipblock-exempt' ) || $user->isAllowed( 'globalblock-exempt' ) ) {
			// User is exempt from IP blocks.
			$result = [ 'block' => null, 'error' => [] ];
			return $result;
		}

		// We have callers from different code paths, better not assuming $ip is null
		// for the non-session user.
		$context = RequestContext::getMain();
		$isSessionUser = $user->equals( $context->getUser() );
		if ( $ip === null && !$isSessionUser && IPUtils::isIPAddress( $user->getName() ) ) {
			// Populate the IP for checking blocks against non-session users.
			$ip = $user->getName();
		}

		$config = $services->getMainConfig();
		if ( $ip !== null ) {
			$ranges = $config->get( 'GlobalBlockingAllowedRanges' );
			foreach ( $ranges as $range ) {
				if ( IPUtils::isInRange( $ip, $range ) ) {
					$result = [ 'block' => null, 'error' => [] ];

					return $result;
				}
			}
		}

		$statsdFactory->increment( 'global_blocking.get_user_block_db_query' );

		$hookRunner = new GlobalBlockingHookRunner( $services->getHookContainer() );

		$lang = $context->getLanguage();
		$block = self::getGlobalBlockingBlock( $ip, !$user->isNamed() );
		if ( $block ) {
			// Check for local whitelisting
			if ( self::getLocalWhitelistInfo( $block->gb_id ) ) {
				// Block has been whitelisted.
				$result = [ 'block' => null, 'error' => [] ];
				return $result;
			}

			$blockTimestamp = $lang->timeanddate( wfTimestamp( TS_MW, $block->gb_timestamp ), true );
			$blockExpiry = $lang->formatExpiry( $block->gb_expiry );
			$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
			$blockingUser = self::maybeLinkUserpage( $block->gb_by_wiki, $block->gb_by );

			// Allow site customization of blocked message.
			if ( IPUtils::isValid( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked';
				$hookRunner->onGlobalBlockingBlockedIpMsg( $errorMsg );
			} elseif ( IPUtils::isValidRange( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked-range';
				$hookRunner->onGlobalBlockingBlockedIpRangeMsg( $errorMsg );
			} else {
				throw new UnexpectedValueException(
					"This should not happen. IP globally blocked is not valid and is not a valid range?"
				);
			}

			$services = MediaWikiServices::getInstance();
			$language = $services->getContentLanguage()->getCode();

			$result = [
				'block' => $block,
				'error' => [
					wfMessage(
						$errorMsg,
						$blockingUser,
						$display_wiki,
						GlobalBlockingServices::wrap( $services )
							->getReasonFormatter()
							->format( $block->gb_reason, $language ),
						$blockTimestamp,
						$blockExpiry,
						$ip,
						$block->gb_address
					)
				],
			];
			return $result;
		}

		$request = $context->getRequest();
		// Checking non-session users are not applicable to the XFF block.
		if ( $config->get( 'GlobalBlockingBlockXFF' ) && $isSessionUser ) {
			$xffIps = $request->getHeader( 'X-Forwarded-For' );
			if ( $xffIps ) {
				$xffIps = array_map( 'trim', explode( ',', $xffIps ) );
				$blocks = self::checkIpsForBlock( $xffIps, !$user->isNamed() );
				if ( count( $blocks ) > 0 ) {
					$appliedBlock = self::getAppliedBlock( $xffIps, $blocks );
					if ( $appliedBlock !== null ) {
						list( $blockIP, $block ) = $appliedBlock;
						$blockTimestamp = $lang->timeanddate(
							wfTimestamp( TS_MW, $block->gb_timestamp ),
							true
						);
						$blockExpiry = $lang->formatExpiry( $block->gb_expiry );
						$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
						$blockingUser = self::maybeLinkUserpage( $block->gb_by_wiki, $block->gb_by );
						// Allow site customization of blocked message.
						$blockedIpXffMsg = 'globalblocking-ipblocked-xff';
						$hookRunner->onGlobalBlockingBlockedIpXffMsg( $blockedIpXffMsg );
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
							'xff' => true,
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
	 * @param string|null $ip The IP address to be checked
	 * @param bool $anon Get anon blocks only
	 * @return stdClass|false The block, or false if none is found
	 */
	public static function getGlobalBlockingBlock( $ip, $anon ) {
		if ( $ip === null ) {
			return false;
		}

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
			if ( !self::getLocalWhitelistInfo( $block->gb_id ) ) {
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
	 * @param int $dbtype either DB_REPLICA or DB_PRIMARY
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	public static function getGlobalBlockingDatabase( $dbtype ) {
		global $wgGlobalBlockingDatabase;

		$factory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $factory->getMainLB( $wgGlobalBlockingDatabase );

		return $lb->getConnection( $dbtype, 'globalblocking', $wgGlobalBlockingDatabase );
	}

	/**
	 * @param string $ip
	 * @param int $dbtype either DB_REPLICA or DB_PRIMARY
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
	 * This is expensive. It involves opening a connection to a new primary database,
	 * and doing a write query. We should only do it when a connection to the primary database
	 * is already open (currently, when a global block is made).
	 *
	 * @param int $limit
	 * @throws DBUnexpectedError
	 */
	public static function purgeExpired( $limit = 1000 ) {
		$globaldbw = self::getGlobalBlockingDatabase( DB_PRIMARY );
		$deleteIds = $globaldbw->selectFieldValues(
			'globalblocks',
			'gb_id',
			[ 'gb_expiry <= ' . $globaldbw->addQuotes( $globaldbw->timestamp() ) ],
			__METHOD__,
			[ 'LIMIT' => $limit ]
		);
		if ( $deleteIds !== [] ) {
			$deleteIds = array_map( 'intval', $deleteIds );
			$globaldbw->delete(
				'globalblocks',
				[ 'gb_id' => $deleteIds ],
				__METHOD__
			);
		}

		// Purge the global_block_whitelist table.
		// We can't be perfect about this without an expensive check on the primary database
		// for every single global block. However, we can be clever about it and store
		// the expiry of global blocks in the global_block_whitelist table.
		// That way, most blocks will fall out of the table naturally when they expire.
		$dbw = wfGetDB( DB_PRIMARY );
		$deleteWhitelistIds = $dbw->selectFieldValues(
			'global_block_whitelist',
			'gbw_id',
			[ 'gbw_expiry <= ' . $dbw->addQuotes( $dbw->timestamp() ) ],
			__METHOD__,
			[ 'LIMIT' => $limit ]
		);
		if ( $deleteWhitelistIds !== [] ) {
			$deleteWhitelistIds = array_map( 'intval', $deleteWhitelistIds );
			$dbw->delete(
				'global_block_whitelist',
				[ 'gbw_id' => $deleteWhitelistIds ],
				__METHOD__
			);
		}
	}

	/**
	 * @param null|int $id
	 * @param null|string $address
	 * @return array|false
	 * @phan-return array{user:int,reason:string}|false
	 * @throws Exception
	 */
	public static function getLocalWhitelistInfo( $id = null, $address = null ) {
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
	 * @return array|false
	 * @phan-return array{user:int,reason:string}|false
	 */
	public static function getLocalWhitelistInfoByIP( $block_ip ) {
		return self::getLocalWhitelistInfo( null, $block_ip );
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
	 * @param string|false $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return StatusValue
	 */
	public static function insertBlock( $address, $reason, $expiry, $blocker, $options = [] ) {
		## Purge expired blocks.
		self::purgeExpired();

		if ( $expiry === false ) {
			return StatusValue::newFatal( 'globalblocking-block-expiryinvalid' );
		}

		$status = self::validateInput( $address );

		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $status->getValue();

		$modify = in_array( 'modify', $options );

		// Check for an existing block in the primary database database
		$existingBlock = self::getGlobalBlockId( $data[ 'ip' ], DB_PRIMARY );
		if ( !$modify && $existingBlock ) {
			return StatusValue::newFatal( 'globalblocking-block-alreadyblocked', $data[ 'ip' ] );
		}

		$lookup = MediaWikiServices::getInstance()->getCentralIdLookup();

		// We're a-ok.
		$dbw = self::getGlobalBlockingDatabase( DB_PRIMARY );

		$anonOnly = in_array( 'anon-only', $options );

		$row = [
			'gb_address' => $data[ 'ip' ],
			'gb_by' => $blocker->getName(),
			'gb_by_central_id' => $lookup->centralIdFromLocalUser( $blocker ),
			'gb_by_wiki' => WikiMap::getCurrentWikiId(),
			'gb_reason' => $reason,
			'gb_timestamp' => $dbw->timestamp( wfTimestampNow() ),
			'gb_anon_only' => $anonOnly,
			'gb_expiry' => $dbw->encodeExpiry( $expiry ),
			'gb_range_start' => $data[ 'rangeStart' ],
			'gb_range_end' => $data[ 'rangeEnd' ],
		];

		if ( $modify && $existingBlock ) {
			$dbw->update( 'globalblocks', $row, [ 'gb_id' => $existingBlock ], __METHOD__ );
			$blockId = $existingBlock;
		} else {
			$dbw->insert( 'globalblocks', $row, __METHOD__, [ 'IGNORE' ] );
			$blockId = $dbw->insertId();
		}

		if ( !$dbw->affectedRows() ) {
			// Race condition?
			return StatusValue::newFatal( 'globalblocking-block-failure', $data[ 'ip' ] );
		}

		return StatusValue::newGood( [
			'id' => $blockId,
		] );
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return StatusValue An empty or fatal status
	 */
	public static function block( $address, $reason, $expiry, $blocker, $options = [] ): StatusValue {
		$expiry = BlockUser::parseExpiryInput( $expiry );
		$status = self::insertBlock( $address, $reason, $expiry, $blocker, $options );

		if ( !$status->isOK() ) {
			return $status;
		}

		$blockId = $status->getValue()['id'];
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
		$logId = $page->addEntry( $logAction,
			Title::makeTitleSafe( NS_USER, $address ),
			$reason,
			[ $info, $address ],
			$blocker
		);
		$page->addRelations( 'gb_id', [ $blockId ], $logId );

		return StatusValue::newGood();
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param User $performer
	 * @return StatusValue An empty or fatal status
	 */
	public static function unblock( string $address, string $reason, User $performer ): StatusValue {
		$status = self::validateInput( $address );

		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $status->getValue();

		$id = self::getGlobalBlockId( $data[ 'ip' ], DB_PRIMARY );
		if ( $id === 0 ) {
			return StatusValue::newFatal( 'globalblocking-notblocked', $data[ 'ip' ] );
		}

		self::getGlobalBlockingDatabase( DB_PRIMARY )->delete(
			'globalblocks',
			[ 'gb_id' => $id ],
			__METHOD__
		);

		$page = new LogPage( 'gblblock' );
		$logId = $page->addEntry(
			'gunblock',
			Title::makeTitleSafe( NS_USER, $data[ 'ip' ] ),
			$reason,
			[],
			$performer
		);
		$page->addRelations( 'gb_id', [ $id ], $logId );

		return StatusValue::newGood();
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
			? $sp->msg( 'parentheses' )
				->rawParams( $sp->getLanguage()->pipeList( $links ) )
				->escaped()
			: '';
		return $linkItems;
	}

	/**
	 * Handles validation and range limits of the IP addresses the user has provided
	 * @param string $address
	 * @return StatusValue Fatal if errors, Good if no errors
	 */
	private static function validateInput( string $address ): StatusValue {
		## Validate input
		$ip = IPUtils::sanitizeIP( $address );

		if ( !$ip || !IPUtils::isIPAddress( $ip ) ) {
			return StatusValue::newFatal( 'globalblocking-block-ipinvalid', $ip );
		}

		if ( IPUtils::isValidRange( $ip ) ) {
			[ $prefix, $range ] = explode( '/', $ip, 2 );
			$limit = MediaWikiServices::getInstance()->getMainConfig()->get( 'GlobalBlockingCIDRLimit' );
			$ipVersion = IPUtils::isIPv4( $prefix ) ? 'IPv4' : 'IPv6';
			if ( (int)$range < $limit[ $ipVersion ] ) {
				return StatusValue::newFatal( 'globalblocking-bigrange', $ip, $ipVersion,
					$limit[ $ipVersion ] );
			}
		}

		$data = [];

		[ $data[ 'rangeStart' ], $data[ 'rangeEnd' ] ] = IPUtils::parseRange( $ip );

		if ( $data[ 'rangeStart' ] !== $data[ 'rangeEnd' ] ) {
			$data[ 'ip' ] = IPUtils::sanitizeRange( $ip );
		} else {
			$data[ 'ip' ] = $ip;
		}

		return StatusValue::newGood( $data );
	}

	public static function selectFields() {
		return [ 'gb_id', 'gb_address', 'gb_by', 'gb_by_wiki', 'gb_reason', 'gb_timestamp',
			'gb_anon_only', 'gb_expiry', 'gb_range_start', 'gb_range_end' ];
	}
}
