<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use InvalidArgumentException;
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
	private CentralIdLookup $centralIdLookup;

	public function __construct(
		IConnectionProvider $dbProvider,
		CentralIdLookup $centralIdLookup
	) {
		$this->dbProvider = $dbProvider;
		$this->centralIdLookup = $centralIdLookup;
	}

	/**
	 * Returns whether the given global block ID or block on a specific target has been
	 * locally disabled.
	 *
	 * @param int|null $id Block ID
	 * @param null|string $target The target of the block (only used if $id is null). Can be an IP, range, or username.
	 * @param string|false $wikiId The wiki where the where the whitelist info should be looked up.
	 *   Use false for the local wiki.
	 * @return array|false false if the block is not locally disabled, otherwise an array containing the
	 *   user ID of the user who disabled the block and the reason for the block being disabled.
	 * @phan-return array{user:int,reason:string}|false
	 */
	public function getLocalWhitelistInfo( ?int $id = null, ?string $target = null, $wikiId = false ) {
		$queryBuilder = $this->dbProvider->getReplicaDatabase( $wikiId )
			->newSelectQueryBuilder()
			->select( [ 'gbw_by', 'gbw_reason' ] )
			->from( 'global_block_whitelist' );
		if ( $id !== null ) {
			$queryBuilder->where( [ 'gbw_id' => $id ] );
		} elseif ( $target !== null ) {
			if ( IPUtils::isIPAddress( $target ) ) {
				$queryBuilder->where( [ 'gbw_address' => $target ] );
			} else {
				$centralIdForTarget = $this->centralIdLookup->centralIdFromName(
					$target, CentralIdLookup::AUDIENCE_RAW
				);
				if ( !$centralIdForTarget ) {
					// If the target does not have a central ID, we cannot look up by username. In this case we can
					// assume that there is no global block and therefore it cannot have a local status.
					return false;
				}
				$queryBuilder->where( [ 'gbw_target_central_id' => $centralIdForTarget ] );
			}
		} else {
			// WTF?
			throw new InvalidArgumentException(
				'Both $target and $id are null when attempting to retrieve whitelist status'
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
