<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use InvalidArgumentException;
use MediaWiki\Block\AutoblockExemptionList;
use MediaWiki\User\CentralId\CentralIdLookup;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Service for looking up whether a global block has been locally disabled.
 *
 * @since 1.42
 */
class GlobalBlockLocalStatusLookup {

	private IConnectionProvider $dbProvider;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private CentralIdLookup $centralIdLookup;
	private AutoblockExemptionList $localAutoblockExemptionList;

	public function __construct(
		IConnectionProvider $dbProvider,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		CentralIdLookup $centralIdLookup,
		AutoblockExemptionList $autoblockExemptionList
	) {
		$this->dbProvider = $dbProvider;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->centralIdLookup = $centralIdLookup;
		$this->localAutoblockExemptionList = $autoblockExemptionList;
	}

	/**
	 * Used to lookup whether a given global block ID is locally disabled on the current wiki when applying
	 * global blocks.
	 *
	 * @param int $id Block ID
	 * @return bool Whether the global block is locally disabled
	 * @internal You probably want to use {@link GlobalBlockLocalStatusLookup::getLocalStatusInfo} instead.
	 *    Only use this method for checking the local status of a global block when you are applying it to
	 *    a user.
	 */
	public function isGlobalBlockLocallyDisabledForBlockApplication( int $id ): bool {
		// Check if the global block with the ID $id is a global autoblock. If it is, then locally disable the block
		// if the autoblocked IP is on the local autoblock exemption list.
		$globalBlockingDbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		$isGlobalBlockAnAutoblock = $globalBlockingDbr->newSelectQueryBuilder()
			->select( 'gb_autoblock_parent_id' )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $id ] )
			->caller( __METHOD__ )
			->fetchField();

		if ( $isGlobalBlockAnAutoblock ) {
			$globallyAutoblockedIPAddress = $globalBlockingDbr->newSelectQueryBuilder()
				->select( 'gb_address' )
				->from( 'globalblocks' )
				->where( [ 'gb_id' => $id ] )
				->caller( __METHOD__ )
				->fetchField();

			$isLocallyExempt = $this->localAutoblockExemptionList->isExempt( $globallyAutoblockedIPAddress );
			if ( $isLocallyExempt ) {
				return true;
			}
		}

		// If the global autoblock local disable checks either did not match or are not applicable, then call
		// ::getLocalStatusInfo to check for the global block being locally disabled through the database table.
		return (bool)$this->getLocalStatusInfo( $id );
	}

	/**
	 * Returns whether the given global block ID has been locally disabled on the given wiki.
	 *
	 * @param int $id Block ID
	 * @param string|false $wikiId The wiki where the where the whitelist info should be looked up.
	 *   Use false for the local wiki.
	 * @return array|false false if the block is not locally disabled, otherwise an array containing the
	 *   user ID of the user who disabled the block and the reason for the block being disabled.
	 * @phan-return array{user:int,reason:string}|false
	 */
	public function getLocalStatusInfo( int $id, $wikiId = false ) {
		$row = $this->dbProvider->getReplicaDatabase( $wikiId )
			->newSelectQueryBuilder()
			->select( [ 'gbw_by', 'gbw_reason' ] )
			->from( 'global_block_whitelist' )
			->where( [ 'gbw_id' => $id ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row === false ) {
			// Not locally disabled.
			return false;
		} else {
			// Block has been locally disabled.
			return [ 'user' => (int)$row->gbw_by, 'reason' => $row->gbw_reason ];
		}
	}

	/**
	 * Returns whether the given global block ID or block on a specific target has been
	 * locally disabled.
	 *
	 * @param int|null $id Block ID
	 * @param null|string $target The target of the block (only used if $id is null). Can be an IP, range, or username.
	 *    You should use the $id over the $target parameter if you have the $id, as using the $id is quicker.
	 * @param string|false $wikiId The wiki where the where the whitelist info should be looked up.
	 *   Use false for the local wiki.
	 * @return array|false false if the block is not locally disabled, otherwise an array containing the
	 *   user ID of the user who disabled the block and the reason for the block being disabled.
	 * @deprecated Since 1.43. Use {@link GlobalBlockLocalStatusLookup::getLocalStatusInfo}, which no longer accepts
	 *   a target parameter but returns the same data when passed a global block ID. To get the global block ID use the
	 *   {@link GlobalBlockLookup::getGlobalBlockId} method.
	 * @phan-return array{user:int,reason:string}|false
	 */
	public function getLocalWhitelistInfo( ?int $id = null, ?string $target = null, $wikiId = false ) {
		if ( $target === null && $id === null ) {
			throw new InvalidArgumentException(
				'Both $target and $id are null when attempting to retrieve whitelist status'
			);
		} elseif ( $target !== null ) {
			$globalBlockIdQueryBuilder = $this->globalBlockingConnectionProvider
				->getReplicaGlobalBlockingDatabase()
				->newSelectQueryBuilder()
				->select( 'gb_id' )
				->from( 'globalblocks' );
			if ( IPUtils::isIPAddress( $target ) ) {
				$globalBlockIdQueryBuilder->where( [ 'gb_address' => $target ] );
			} else {
				$centralIdForTarget = $this->centralIdLookup->centralIdFromName(
					$target, CentralIdLookup::AUDIENCE_RAW
				);
				if ( !$centralIdForTarget ) {
					// If the target does not have a central ID, we cannot look up by username. In this case we can
					// assume that there is no global block and therefore it cannot have a local status.
					return false;
				}
				$globalBlockIdQueryBuilder->where( [ 'gb_target_central_id' => $centralIdForTarget ] );
			}
			$id = $globalBlockIdQueryBuilder->fetchField();
			if ( !$id ) {
				// Not locally disabled if there is no global block on the target
				return false;
			}
		}

		return $this->getLocalStatusInfo( $id, $wikiId );
	}

	/**
	 * Returns whether a global block on the given IP address has been locally disabled.
	 *
	 * @param string $block_ip
	 * @param string|false $wikiId The wiki where the where the whitelist info should be looked up.
	 *    Use false for the local wiki.
	 * @return array|false false if the block is not locally disabled, otherwise an array containing the
	 *    user ID of the user who disabled the block and the reason for the block being disabled.
	 * @phan-return array{user:int,reason:string}|false
	 * @deprecated since 1.42. Use ::getLocalWhitelistInfo.
	 */
	public function getLocalWhitelistInfoByIP( string $block_ip, $wikiId = false ) {
		wfDeprecated( __METHOD__, '1.42' );
		return $this->getLocalWhitelistInfo( null, $block_ip, $wikiId );
	}
}
