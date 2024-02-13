<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\ReadOnlyMode;

/**
 * Purges expired block rows from the globalblocks and global_block_whitelist tables.
 *
 * @since 1.42
 */
class GlobalBlockingBlockPurger {

	public const CONSTRUCTOR_OPTIONS = [
		MainConfigNames::UpdateRowsPerQuery,
	];

	private ServiceOptions $options;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private IConnectionProvider $connectionProvider;
	private ReadOnlyMode $readOnlyMode;

	public function __construct(
		ServiceOptions $options,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		IConnectionProvider $connectionProvider,
		ReadOnlyMode $readOnlyMode
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->connectionProvider = $connectionProvider;
		$this->readOnlyMode = $readOnlyMode;
	}

	/**
	 * Purge stale block rows.
	 *
	 * This should only be called on a request that performs writes to the database,
	 * such as creating a block, as this is an expensive operation.
	 *
	 * This acts similarly to DatabaseBlockStore::purgeExpiredBlocks, but the purge is not performed
	 * on POSTSEND.
	 */
	public function purgeExpiredBlocks() {
		$globaldbw = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		if ( !$this->readOnlyMode->isReadOnly( $globaldbw->getDomainID() ) ) {
			$deleteIds = $globaldbw->newSelectQueryBuilder()
				->select( 'gb_id' )
				->from( 'globalblocks' )
				->where( $globaldbw->expr( 'gb_expiry', '<=', $globaldbw->timestamp() ) )
				->limit( $this->options->get( MainConfigNames::UpdateRowsPerQuery ) )
				->caller( __METHOD__ )
				->fetchFieldValues();
			if ( $deleteIds !== [] ) {
				$deleteIds = array_map( 'intval', $deleteIds );
				$globaldbw->newDeleteQueryBuilder()
					->deleteFrom( 'globalblocks' )
					->where( [ 'gb_id' => $deleteIds ] )
					->caller( __METHOD__ )
					->execute();
			}
		}

		$dbw = $this->connectionProvider->getPrimaryDatabase();
		if ( !$this->readOnlyMode->isReadOnly() ) {
			// Purge the global_block_whitelist table.
			// We can't be perfect about this without an expensive check on the primary database
			// for every single global block. However, we can be clever about it and store
			// the expiry of global blocks in the global_block_whitelist table.
			// That way, most blocks will fall out of the table naturally when they expire.
			$deleteWhitelistIds = $dbw->newSelectQueryBuilder()
				->select( 'gbw_id' )
				->from( 'global_block_whitelist' )
				->where( $dbw->expr( 'gbw_expiry', '<=', $dbw->timestamp() ) )
				->limit( $this->options->get( MainConfigNames::UpdateRowsPerQuery ) )
				->caller( __METHOD__ )
				->fetchFieldValues();
			if ( $deleteWhitelistIds !== [] ) {
				$deleteWhitelistIds = array_map( 'intval', $deleteWhitelistIds );
				$dbw->newDeleteQueryBuilder()
					->deleteFrom( 'global_block_whitelist' )
					->where( [ 'gbw_id' => $deleteWhitelistIds ] )
					->caller( __METHOD__ )
					->execute();
			}
		}
	}
}
