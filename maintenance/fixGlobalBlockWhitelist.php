<?php

namespace MediaWiki\Extension\GlobalBlocking\Maintenance;

use Maintenance;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * If there is a whitelisted IP address or range with a corresponding global block
 * row but if the ids do not match, this script can be used to make the ids same so
 * that the whitelist is effective. Optionally, entries in the whitelist table with
 * no corresponding global block row can be deleted if the 'delete' option is enabled.
 * See https://phabricator.wikimedia.org/T56496.
 */
class FixGlobalBlockWhitelist extends Maintenance {

	protected bool $dryRun = false;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'delete', 'Delete whitelist entries with no corresponding global block' );
		$this->addOption( 'dry-run', 'Run the script without any modifications' );
		$this->setBatchSize( 500 );

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
		$fixableBroken = [];
		$unfixableBroken = [];
		do {
			// Select a batch of whitelist entries to check which start from a gbw_id greater than the greatest gbw_id
			// from the last batch.
			$localWhitelistEntries = $localDbr->newSelectQueryBuilder()
				->select( [ 'gbw_id', 'gbw_address' ] )
				->from( 'global_block_whitelist' )
				->where( $localDbr->expr( 'gbw_id', '>', $lastGlobalBlockId ) )
				->orderBy( 'gbw_id' )
				->limit( $this->getBatchSize() ?? 500 )
				->caller( __METHOD__ )
				->fetchResultSet();

			$whitelistEntries = [];
			foreach ( $localWhitelistEntries as $row ) {
				$whitelistEntries[ $row->gbw_id ] = $row->gbw_address;
				$lastGlobalBlockId = $row->gbw_id;
			}

			$whitelistedIPs = array_values( $whitelistEntries );

			// If there were no whitelist entries in the batch, then exit now as there is nothing more to do.
			if ( !count( $whitelistedIPs ) ) {
				break;
			}

			// Find the associated global block rows for the whitelist entries in this batch.
			$globalBlockingDbr = GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockingConnectionProvider()
				->getReplicaGlobalBlockingDatabase();
			$gblocks = $globalBlockingDbr->newSelectQueryBuilder()
				->select( [ 'gb_id', 'gb_address' ] )
				->from( 'globalblocks' )
				->where( [ 'gb_address' => $whitelistedIPs ] )
				->caller( __METHOD__ )
				->fetchResultSet();

			$gblockEntries = [];
			foreach ( $gblocks as $gblock ) {
				$gblockEntries[ $gblock->gb_id ] = $gblock->gb_address;
			}

			// Try to match the whitelist entries with the global block entries.
			foreach ( $gblockEntries as $gblockId => $gblockAddress ) {
				$whitelistId = array_search( $gblockAddress, $whitelistEntries );
				if ( $whitelistId !== false && $whitelistId !== $gblockId ) {
					// If there is a whitelist entry which has the same target as a global block, but the IDs of these
					// do not match, then this is a broken whitelist entry which can be fixed.
					$fixableBroken[ $gblockId ] = $whitelistEntries[ $whitelistId ];
				}
			}

			// Find any whitelist entries that do not have a corresponding global block. These are broken entries but
			// cannot be fixed and will be deleted if the 'delete' option is specified.
			$unfixableBroken = array_merge(
				$unfixableBroken,
				array_diff( $whitelistedIPs, array_values( $gblockEntries ) )
			);
		} while ( $localWhitelistEntries->numRows() === ( $this->getBatchSize() ?? 500 ) );

		$this->fixBrokenWhitelist( $fixableBroken );

		if ( $this->getOption( 'delete' ) ) {
			$this->handleDeletions( $unfixableBroken );
		}
	}

	/**
	 * Fixes broken whitelist entries which have a corresponding global block but the IDs do not match.
	 *
	 * @param array $brokenEntries An array of whitelist entries which have a corresponding global block but the IDs
	 *   do not match. The key is the global block ID for the currently applied block and the value is the target of
	 *   that block.
	 * @return void
	 */
	protected function fixBrokenWhitelist( array $brokenEntries ) {
		$brokenCount = count( $brokenEntries );
		if ( $brokenCount === 0 ) {
			// Return early if there are no broken whitelist entries that can be fixed.
			$this->output( "No broken whitelist entries which can be fixed.\n" );
			return;
		}

		// Start processing the broken whitelist entries that can be fixed.
		$this->output( "Found $brokenCount broken whitelist entries which can be fixed.\n" );
		$count = 0;
		$lbFactory = $this->getServiceContainer()->getDBLoadBalancerFactory();
		$localDbr = $this->getReplicaDB();
		$localDbw = $this->getPrimaryDB();

		foreach ( $brokenEntries as $newId => $address ) {
			if ( !$this->dryRun && $count === $this->mBatchSize ) {
				// Wait for replication if we have processed a batch of entries
				// and this is not a dry run.
				$lbFactory->waitForReplication();
				$count = 0;
			}
			$count++;

			// Check if there is already a whitelist entry using the id we want to use.
			$entryAlreadyExists = (bool)$localDbr->newSelectQueryBuilder()
				->select( '1' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_id' => $newId ] )
				->fetchField();
			if ( $entryAlreadyExists ) {
				if ( $this->dryRun ) {
					$this->output( " Would delete broken entries for $address: id $newId already is whitelisted.\n" );
					continue;
				}
				// If a whitelist entry already exists with the gbw_id we want to use, then we cannot update this
				// broken whitelist entry and should instead delete it.
				$localDbw->newDeleteQueryBuilder()
					->deleteFrom( 'global_block_whitelist' )
					->where( [
						'gbw_address' => $address,
						// Only delete the broken entries and not the unbroken entry.
						$localDbw->expr( 'gbw_id', '!=', $newId )
					] )
					->caller( __METHOD__ )
					->execute();
				$this->output( " Deleted broken entries for $address: id $newId already is whitelisted.\n" );
				continue;
			}

			// Delete any duplicate whitelist entries with the same address, keeping the one with the highest
			// gbw_id as this should be the most recent entry.
			$brokenEntriesForThisAddress = $localDbr->newSelectQueryBuilder()
				->select( 'gbw_id' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_address' => $address ] )
				->caller( __METHOD__ )
				->fetchFieldValues();
			if ( count( $brokenEntriesForThisAddress ) > 1 ) {
				// If there are multiple broken entries for this address, then delete all but the one with the highest
				// gbw_id as this will likely be the most relevant entry (as it was for the most recent global block
				// on this target).
				$maxIdForThisAddress = max( $brokenEntriesForThisAddress );
				if ( $this->dryRun ) {
					$this->output(
						" Would delete all whitelist entries for $address except the entry with gbw_id as " .
						"$maxIdForThisAddress: only one row can be updated to use id $newId\n."
					);
				} else {
					$localDbw->newDeleteQueryBuilder()
						->deleteFrom( 'global_block_whitelist' )
						->where( [
							'gbw_address' => $address,
							$localDbw->expr( 'gbw_id', '!=', $maxIdForThisAddress )
						] )
						->caller( __METHOD__ )
						->execute();
					$this->output(
						" Deleted all whitelist entries for $address except the entry with gbw_id as " .
						"$maxIdForThisAddress: only one row can be updated to use id $newId\n."
					);
				}
			}

			// Update the one remaining broken whitelist entry to use the correct id, and also to match the expiry
			// and target central ID of the associated global block.
			if ( $this->dryRun ) {
				$this->output( " Whitelist broken $address: current gb_id is $newId\n" );
				continue;
			}
			$globalBlockingDbr = GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockingConnectionProvider()
				->getReplicaGlobalBlockingDatabase();
			$associatedGlobalBlockEntry = $globalBlockingDbr->newSelectQueryBuilder()
				->select( [ 'gb_expiry', 'gb_target_central_id' ] )
				->from( 'globalblocks' )
				->where( [ 'gb_id' => $newId ] )
				->caller( __METHOD__ )
				->fetchRow();
			$localDbw->newUpdateQueryBuilder()
				->update( 'global_block_whitelist' )
				->set( [
					'gbw_id' => $newId,
					'gbw_expiry' => $associatedGlobalBlockEntry->gb_expiry,
					'gbw_target_central_id' => $associatedGlobalBlockEntry->gb_target_central_id
				] )
				->where( [ 'gbw_address' => $address ] )
				->caller( __METHOD__ )
				->execute();
			$this->output( " Fixed $address: id changed to $newId\n" );
		}
		$this->output( "Finished processing broken whitelist entries.\n" );
	}

	/**
	 * Handles the deletion of whitelist entries which have no corresponding global block.
	 * Only called if the 'delete' option is specified.
	 *
	 * @param array $nonExistent An array of targets which have whitelist entries but no corresponding global block.
	 * @return void
	 */
	protected function handleDeletions( array $nonExistent ) {
		$nonExistentCount = count( $nonExistent );
		if ( $nonExistentCount === 0 ) {
			// Return early if there are no whitelist entries to be deleted.
			$this->output( "All whitelist entries have corresponding global blocks.\n" );
			return;
		}
		$this->output( "Found $nonExistentCount whitelist entries with no corresponding global blocks:\n"
			. implode( "\n", $nonExistent ) . "\n"
		);
		if ( !$this->dryRun ) {
			// Delete the whitelist entries which have no corresponding global block in batches of 'batch-size'
			// targets.
			foreach ( array_chunk( $nonExistent, $this->getBatchSize() ?? 500 ) as $chunk ) {
				$this->getPrimaryDB()->newDeleteQueryBuilder()
					->deleteFrom( 'global_block_whitelist' )
					->where( [ 'gbw_address' => $chunk ] )
					->caller( __METHOD__ )
					->execute();
			}
			$this->output( "Finished deleting whitelist entries with no corresponding global blocks.\n" );
		}
	}
}

$maintClass = FixGlobalBlockWhitelist::class;
require_once RUN_MAINTENANCE_IF_MAIN;
