<?php

namespace MediaWiki\Extension\GlobalBlocking;

use Exception;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use StatusValue;
use stdClass;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\IExpression;

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
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getUserBlock( $user, $ip );
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
	 * @param bool $anon Include anon-only blocks
	 * @return stdClass|false The block, or false if none is found
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getGlobalBlockingBlock.
	 */
	public static function getGlobalBlockingBlock( $ip, $anon ) {
		wfDeprecated( __METHOD__, '1.42' );
		$flags = GlobalBlockLookup::SKIP_LOCAL_DISABLE_CHECK;
		if ( !$anon ) {
			$flags |= GlobalBlockLookup::SKIP_SOFT_IP_BLOCKS;
		}
		// Don't attempt to pass a central ID as this deprecated method does not support it,
		// so pass 0 (which is the value to indicate no user-based blocks should be checked).
		$result = GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getGlobalBlockingBlock( $ip, 0, $flags );
		if ( $result === null ) {
			return false;
		}
		return $result;
	}

	/**
	 * Get a database range condition for an IP address
	 * @param string $ip The IP address or range
	 * @return IExpression[] a SQL condition
	 * @deprecated Since 1.42. Use GlobalBlockLookup::getRangeCondition.
	 */
	public static function getRangeCondition( $ip ) {
		wfDeprecated( __METHOD__, '1.42' );
		$conds = GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLookup()
			->getRangeCondition( $ip );
		return [ $conds ];
	}

	/**
	 * @deprecated Since 1.42. Use GlobalBlockingConnectionProvider::getPrimaryGlobalBlockingDatabase.
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	public static function getPrimaryGlobalBlockingDatabase() {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockingConnectionProvider()
			->getPrimaryGlobalBlockingDatabase();
	}

	/**
	 * @deprecated Since 1.42. Use GlobalBlockingConnectionProvider::getReplicaGlobalBlockingDatabase.
	 * @return \Wikimedia\Rdbms\IReadableDatabase
	 */
	public static function getReplicaGlobalBlockingDatabase() {
		wfDeprecated( __METHOD__, '1.42' );
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
		wfDeprecated( __METHOD__, '1.42' );
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
		wfDeprecated( __METHOD__, '1.42' );
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
		wfDeprecated( __METHOD__, '1.42' );
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
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLocalStatusLookup()
			->getLocalWhitelistInfo( $id, $address );
	}

	/**
	 * @param string $block_ip
	 * @return array|false
	 * @phan-return array{user:int,reason:string}|false
	 * @deprecated Since 1.42. Use GlobalBlockLocalStatusLookup::getLocalWhitelistInfo.
	 */
	public static function getLocalWhitelistInfoByIP( $block_ip ) {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockLocalStatusLookup()
			->getLocalWhitelistInfoByIP( $block_ip );
	}

	/**
	 * @param string $wiki_id
	 * @param string $user
	 * @return string
	 * @deprecated Since 1.42. Use GlobalBlockingLinkBuilder::maybeLinkUserpage.
	 */
	public static function maybeLinkUserpage( $wiki_id, $user ) {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockingLinkBuilder()
			->maybeLinkUserpage( $wiki_id, $user, RequestContext::getMain()->getTitle() );
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string|false $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return StatusValue
	 * @deprecated Since 1.42. Use GlobalBlockManager::block which will also create a log entry.
	 */
	public static function insertBlock( $address, $reason, $expiry, $blocker, $options = [] ) {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockManager()
			->insertBlock( $address, $reason, $expiry, $blocker, $options );
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return StatusValue An empty or fatal status
	 * @deprecated Since 1.42. Use GlobalBlockManager::block.
	 */
	public static function block( $address, $reason, $expiry, $blocker, $options = [] ): StatusValue {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockManager()
			->block( $address, $reason, $expiry, $blocker, $options );
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param User $performer
	 * @return StatusValue An empty or fatal status
	 * @deprecated Since 1.42. Use GlobalBlockManager::unblock.
	 */
	public static function unblock( string $address, string $reason, User $performer ): StatusValue {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockManager()
			->unblock( $address, $reason, $performer );
	}

	/**
	 * Build links to other global blocking special pages, shown in the subtitle
	 * @param SpecialPage $sp SpecialPage instance for context
	 * @return string links to special pages
	 * @deprecated Since 1.42. Use GlobalBlockingLinkBuilder::buildSubtitleLinks.
	 */
	public static function buildSubtitleLinks( SpecialPage $sp ) {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )
			->getGlobalBlockingLinkBuilder()
			->buildSubtitleLinks( $sp );
	}

	/**
	 * @deprecated Since 1.42. Use GlobalBlockLookup::selectFields instead.
	 */
	public static function selectFields() {
		wfDeprecated( __METHOD__, '1.42' );
		return GlobalBlockLookup::selectFields();
	}
}
