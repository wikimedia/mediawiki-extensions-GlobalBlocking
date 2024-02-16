<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use InvalidArgumentException;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Service for looking up whether a global block has been locally disabled.
 *
 * @since 1.42
 */
class GlobalBlockLocalStatusLookup {

	private IConnectionProvider $dbProvider;

	public function __construct( IConnectionProvider $dbProvider ) {
		$this->dbProvider = $dbProvider;
	}

	/**
	 * Returns whether the given global block ID or block on a specific target has been
	 * locally disabled.
	 *
	 * @param int|null $id Block ID
	 * @param null|string $address The target of the block (only used if $id is null).
	 * @return array|false false if the block is not locally disabled, otherwise an array containing the
	 *   user ID of the user who disabled the block and the reason for the block being disabled.
	 * @phan-return array{user:int,reason:string}|false
	 */
	public function getLocalWhitelistInfo( ?int $id = null, ?string $address = null ) {
		$queryBuilder = $this->dbProvider->getReplicaDatabase()
			->newSelectQueryBuilder()
			->select( [ 'gbw_by', 'gbw_reason' ] )
			->from( 'global_block_whitelist' );
		if ( $id != null ) {
			$queryBuilder->where( [ 'gbw_id' => $id ] );
		} elseif ( $address != null ) {
			$queryBuilder->where( [ 'gbw_address' => $address ] );
		} else {
			// WTF?
			throw new InvalidArgumentException(
				'Neither Block IP nor Block ID given for retrieving whitelist status'
			);
		}

		$row = $queryBuilder
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
	 * Returns whether a global block on the given IP address has been locally disabled.
	 *
	 * @param string $block_ip
	 * @return array|false false if the block is not locally disabled, otherwise an array containing the
	 *    user ID of the user who disabled the block and the reason for the block being disabled.
	 * @phan-return array{user:int,reason:string}|false
	 */
	public function getLocalWhitelistInfoByIP( string $block_ip ) {
		return $this->getLocalWhitelistInfo( null, $block_ip );
	}
}
