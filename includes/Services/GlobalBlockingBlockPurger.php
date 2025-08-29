<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MainConfigNames;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\ReadOnlyMode;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Purges expired block rows from the globalblocks and global_block_whitelist tables.
 *
 * @since 1.42
 */
class GlobalBlockingBlockPurger {

	public const CONSTRUCTOR_OPTIONS = [
		MainConfigNames::UpdateRowsPerQuery,
	];

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		private readonly IConnectionProvider $connectionProvider,
		private readonly ReadOnlyMode $readOnlyMode,
		private readonly GlobalBlockLookup $globalBlockLookup,
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * Purge stale block rows.
	 *
	 * This should only be called on a request that performs writes to the database,
	 * such as creating a block, as this is an expensive operation.
	 *
	 * This acts similarly to DatabaseBlockStore::purgeExpiredBlocks, but the purge is not performed
	 * on POSTSEND.
	 *
	 * @param null|string $target If this is called in the context of creating or managing a global block,
	 *   provide the name of the target to ensure that expired global blocks are removed for this target.
	 */
	public function purgeExpiredBlocks( ?string $target = null ) {
		$globaldbw = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		if ( !$this->readOnlyMode->isReadOnly( $globaldbw->getDomainID() ) ) {
			// Prioritise removing any expired global block on the current target, so that block modifications on
			// expired blocks are skipped and instead a new global block is created.
			$totalLimitLeft = $this->options->get( MainConfigNames::UpdateRowsPerQuery );
			if ( $target !== null ) {
				$targetGlobalBlockId = $this->globalBlockLookup->getGlobalBlockId(
					$target, DB_REPLICA, GlobalBlockLookup::SKIP_EXPIRY_CHECK
				);

				if ( $targetGlobalBlockId ) {
					$globaldbw->newDeleteQueryBuilder()
						->deleteFrom( 'globalblocks' )
						->where( $globaldbw->expr( 'gb_expiry', '<=', $globaldbw->timestamp() ) )
						->andWhere(
							$globaldbw->expr( 'gb_id', '=', $targetGlobalBlockId )
								->or( 'gb_autoblock_parent_id', '=', $targetGlobalBlockId )
						)
						->caller( __METHOD__ )
						->execute();
					$totalLimitLeft -= $globaldbw->affectedRows();
				}
			}

			if ( $totalLimitLeft > 0 ) {
				$deleteIds = $globaldbw->newSelectQueryBuilder()
					->select( 'gb_id' )
					->from( 'globalblocks' )
					->where( $globaldbw->expr( 'gb_expiry', '<=', $globaldbw->timestamp() ) )
					->orderBy( 'gb_expiry', SelectQueryBuilder::SORT_ASC )
					->limit( $totalLimitLeft )
					->caller( __METHOD__ )
					->fetchFieldValues();
				if ( count( $deleteIds ) ) {
					$deleteIds = array_map( 'intval', $deleteIds );
					$globaldbw->newDeleteQueryBuilder()
						->deleteFrom( 'globalblocks' )
						->where( [ 'gb_id' => $deleteIds ] )
						->caller( __METHOD__ )
						->execute();
				}
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
				->orderBy( 'gbw_expiry', SelectQueryBuilder::SORT_ASC )
				->limit( $this->options->get( MainConfigNames::UpdateRowsPerQuery ) )
				->caller( __METHOD__ )
				->fetchFieldValues();
			if ( count( $deleteWhitelistIds ) ) {
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
