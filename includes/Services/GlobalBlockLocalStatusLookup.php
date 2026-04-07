<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Block\AutoblockExemptionList;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Service for looking up whether a global block has been locally disabled.
 *
 * @since 1.42
 */
class GlobalBlockLocalStatusLookup {

	public function __construct(
		private readonly IConnectionProvider $dbProvider,
		private readonly GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		private readonly AutoblockExemptionList $localAutoblockExemptionList,
	) {
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
	 * @param string|false $wikiId The wiki where the where the local disable status should be looked up.
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
	 * Returns a list of global block IDs that are disabled given a list of global block IDs to check
	 * and a wiki to check on.
	 *
	 * @since 1.46
	 * @param int[] $ids A list of global block IDs to check
	 * @param string|false $wikiId The wiki where the local status should be checked,
	 *   or false for the local wiki
	 * @return int[] The list of global block IDs from $ids that are locally disabled
	 */
	public function getLocallyDisabledGlobalBlockIds( array $ids, string|false $wikiId = false ): array {
		if ( count( $ids ) === 0 ) {
			return [];
		}

		$locallyDisabledGlobalBlockIds = $this->dbProvider->getReplicaDatabase( $wikiId )
			->newSelectQueryBuilder()
			->select( 'gbw_id' )
			->from( 'global_block_whitelist' )
			->where( [ 'gbw_id' => $ids ] )
			->caller( __METHOD__ )
			->fetchFieldValues();
		return array_map( 'intval', $locallyDisabledGlobalBlockIds );
	}
}
