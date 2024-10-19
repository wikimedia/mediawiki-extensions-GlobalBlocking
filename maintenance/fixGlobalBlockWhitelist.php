<?php

namespace MediaWiki\Extension\GlobalBlocking\Maintenance;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Maintenance\Maintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * This script can be used to purge global_block_whitelist rows which have no
 * corresponding globalblocks table row.
 */
class FixGlobalBlockWhitelist extends Maintenance {

	protected bool $dryRun = false;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'dry-run', 'Run the script without any modifications' );
		$this->setBatchSize( 500 );

		// Allow unregistered options so that users of the script which have specified the --delete option
		// do not break.
		$this->setAllowUnregisteredOptions( true );

		$this->requireExtension( 'GlobalBlocking' );
	}

	public function execute() {
		$this->dryRun = $this->getOption( 'dry-run', false ) !== false;
		$localDbr = $this->getReplicaDB();

		// First check if there are any rows in global_block_whitelist. If there are no rows, then exit now as there is
		// nothing for this script to do.
		$rowsExist = $localDbr->newSelectQueryBuilder()
			->select( 'gbw_id' )
			->from( 'global_block_whitelist' )
			->caller( __METHOD__ )
			->limit( 1 )
			->fetchRowCount();

		if ( !$rowsExist ) {
			$this->output( "No whitelist entries.\n" );
			return;
		}

		$lastGlobalBlockId = 0;
		$broken = [];
		do {
			// Select a batch of whitelist entries to check which start from a gbw_id greater than the greatest gbw_id
			// from the last batch.
			$localWhitelistIds = $localDbr->newSelectQueryBuilder()
				->select( 'gbw_id' )
				->from( 'global_block_whitelist' )
				->where( $localDbr->expr( 'gbw_id', '>', $lastGlobalBlockId ) )
				->orderBy( 'gbw_id' )
				->limit( $this->getBatchSize() ?? 500 )
				->caller( __METHOD__ )
				->fetchFieldValues();

			// If there were no whitelist entries in the batch, then exit now as there is nothing more to do.
			if ( !count( $localWhitelistIds ) ) {
				break;
			}

			// Find the associated global block rows for the whitelist entries in this batch.
			$globalBlockingDbr = GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockingConnectionProvider()
				->getReplicaGlobalBlockingDatabase();
			$matchingGlobalBlockIds = $globalBlockingDbr->newSelectQueryBuilder()
				->select( 'gb_id' )
				->from( 'globalblocks' )
				->where( [ 'gb_id' => $localWhitelistIds ] )
				->caller( __METHOD__ )
				->fetchFieldValues();

			$broken = array_merge( $broken, array_diff( $localWhitelistIds, $matchingGlobalBlockIds ) );
		} while ( count( $localWhitelistIds ) === ( $this->getBatchSize() ?? 500 ) );

		$this->handleDeletions( $broken );
	}

	/**
	 * Handles the deletion of whitelist entries which have no corresponding global block.
	 *
	 * @param array $nonExistent An array of gbw_ids which have no corresponding global block
	 * @return void
	 */
	protected function handleDeletions( array $nonExistent ) {
		$nonExistentCount = count( $nonExistent );
		if ( $nonExistentCount === 0 ) {
			// Return early if there are no whitelist entries to be deleted.
			$this->output( "All whitelist entries have corresponding global blocks.\n" );
			return;
		}
		$this->output( "Found $nonExistentCount whitelist entries with no corresponding global blocks with IDs:\n"
			. implode( "\n", $nonExistent ) . "\n"
		);
		if ( !$this->dryRun ) {
			// Delete the whitelist entries which have no corresponding global block in batches of 'batch-size'
			// targets.
			foreach ( array_chunk( $nonExistent, $this->getBatchSize() ?? 500 ) as $chunk ) {
				$this->getPrimaryDB()->newDeleteQueryBuilder()
					->deleteFrom( 'global_block_whitelist' )
					->where( [ 'gbw_id' => $chunk ] )
					->caller( __METHOD__ )
					->execute();
			}
			$this->output( "Finished deleting whitelist entries with no corresponding global blocks.\n" );
		}
	}
}

$maintClass = FixGlobalBlockWhitelist::class;
require_once RUN_MAINTENANCE_IF_MAIN;
