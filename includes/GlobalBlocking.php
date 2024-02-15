<?php

namespace MediaWiki\Extension\GlobalBlocking;

use Exception;
use LogPage;
use MediaWiki\Block\BlockUser;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;
use Message;
use StatusValue;
use stdClass;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\Expression;

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
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getUserBlock.
	 */
	public static function getUserBlock( $user, $ip ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getUserBlock( $user, $ip );
	}

	/**
	 * @param User $user
	 * @param string $ip
	 * @return Message[] empty or message objects
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getUserBlockErrors.
	 */
	public static function getUserBlockErrors( $user, $ip ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getUserBlockErrors( $user, $ip );
	}

	/**
	 * @deprecated Since 1.42 without replacement.
	 */
	public static function getTargetType( $target ) {
		wfDeprecated( __METHOD__, '1.42' );
		if ( IPUtils::isValid( $target ) ) {
			return self::TYPE_IP;
		} elseif ( IPUtils::isValidRange( $target ) ) {
			return self::TYPE_RANGE;
		}
	}

	/**
	 * Get a block
	 * @param string|null $ip The IP address to be checked
	 * @param bool $anon Get anon blocks only
	 * @return stdClass|false The block, or false if none is found
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getGlobalBlockingBlock.
	 */
	public static function getGlobalBlockingBlock( $ip, $anon ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getGlobalBlockingBlock( $ip, $anon );
	}

	/**
	 * Get a database range condition for an IP address
	 * @param string $ip The IP address or range
	 * @return Expression[] a SQL condition
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getRangeCondition.
	 */
	public static function getRangeCondition( $ip ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getRangeCondition( $ip );
	}

	/**
	 * @deprecated Since 1.42. Use GlobalBlockingConnectionProvider::getPrimaryGlobalBlockingDatabase.
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	public static function getPrimaryGlobalBlockingDatabase() {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockingConnectionProvider()
			->getPrimaryGlobalBlockingDatabase();
	}

	/**
	 * @deprecated Since 1.42. Use GlobalBlockingConnectionProvider::getReplicaGlobalBlockingDatabase.
	 * @return \Wikimedia\Rdbms\IReadableDatabase
	 */
	public static function getReplicaGlobalBlockingDatabase() {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockingConnectionProvider()
			->getReplicaGlobalBlockingDatabase();
	}

	/**
	 * @param int $dbtype either DB_REPLICA or DB_PRIMARY
	 * @deprecated Since 1.42. Use GlobalBlockingConnectionProvider to get a database connection.
	 * @return \Wikimedia\Rdbms\IDatabase|\Wikimedia\Rdbms\IReadableDatabase
	 */
	public static function getGlobalBlockingDatabase( $dbtype ) {
		if ( $dbtype == DB_PRIMARY ) {
			return self::getPrimaryGlobalBlockingDatabase();
		} else {
			return self::getReplicaGlobalBlockingDatabase();
		}
	}

	/**
	 * @param string $ip
	 * @param int $dbtype either DB_REPLICA or DB_PRIMARY
	 * @return int
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getGlobalBlockId.
	 */
	public static function getGlobalBlockId( $ip, $dbtype = DB_REPLICA ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getGlobalBlockId( $ip, $dbtype );
	}

	/**
	 * Purge stale block rows.
	 *
	 * This is expensive. It involves opening a connection to a new primary database,
	 * and doing a write query. We should only do it when a connection to the primary database
	 * is already open (currently, when a global block is made).
	 *
	 * @throws DBUnexpectedError
	 * @deprecated Since 1.42. Use GlobalBlockingBlockPurger::purgeExpiredBlocks.
	 */
	public static function purgeExpired() {
		GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockingBlockPurger()
			->purgeExpiredBlocks();
	}

	/**
	 * @param null|int $id
	 * @param null|string $address
	 * @return array|false
	 * @phan-return array{user:int,reason:string}|false
	 * @throws Exception
	 * @deprecated Since 1.42. Use GlobalBlockLocalStatusLookup::getLocalWhitelistInfo.
	 */
	public static function getLocalWhitelistInfo( $id = null, $address = null ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLocalStatusLookup()
			->getLocalWhitelistInfo( $id, $address );
	}

	/**
	 * @param string $block_ip
	 * @return array|false
	 * @phan-return array{user:int,reason:string}|false
	 * @deprecated Since 1.42. Use GlobalBlockLocalStatusLookup::getLocalWhitelistInfoByIP.
	 */
	public static function getLocalWhitelistInfoByIP( $block_ip ) {
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLocalStatusLookup()
			->getLocalWhitelistInfoByIP( $block_ip );
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
		$dbw = self::getPrimaryGlobalBlockingDatabase();

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

		self::getPrimaryGlobalBlockingDatabase()->delete(
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

	/**
	 * @deprecated Since 1.42. Use GlobalBlockLookup::selectFields instead.
	 */
	public static function selectFields() {
		return GlobalBlockLookup::selectFields();
	}
}
