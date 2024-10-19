<?php

namespace MediaWiki\Extension\GlobalBlocking\Maintenance;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use Wikimedia\Rdbms\SelectQueryBuilder;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

/**
 * Maintenance script for updating gb_autoblock_parent_id to replace NULL values with 0.
 */
class UpdateAutoBlockParentIdColumn extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			"Used to update the values of NULL for gb_autoblock_parent_id to 0 (T376340). Necessary because " .
			"the gb_autoblock_parent_id is used in a unique index which does not work as intended with NULL values. " .
			"If you use a central globalblocks table, you only need to run this script once for the wikis which " .
			"use the central table."
		);

		$this->requireExtension( 'GlobalBlocking' );
	}

	/** @inheritDoc */
	public function getUpdateKey() {
		return __CLASS__;
	}

	/** @inheritDoc */
	public function doDbUpdates() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$dbr = $globalBlockingServices->getGlobalBlockingConnectionProvider()->getReplicaGlobalBlockingDatabase();

		$hasRowsToUpdate = $dbr->newSelectQueryBuilder()
			->select( '1' )
			->from( 'globalblocks' )
			->where( [ 'gb_autoblock_parent_id' => null ] )
			->caller( __METHOD__ )
			->fetchField();
		if ( !$hasRowsToUpdate ) {
			$this->output( "The globalblocks table has no rows to update.\n" );
			return true;
		}

		$success = 0;
		$failed = 0;
		$lastProcessedRowId = 0;
		$dbw = $globalBlockingServices->getGlobalBlockingConnectionProvider()->getPrimaryGlobalBlockingDatabase();
		do {
			// Fetch a batch of rows with gb_autoblock_parent_id as NULL
			$batchToProcess = $dbr->newSelectQueryBuilder()
				->select( 'gb_id' )
				->from( 'globalblocks' )
				->where( [
					'gb_autoblock_parent_id' => null,
					$dbr->expr( 'gb_id', '>', $lastProcessedRowId ),
				] )
				->orderBy( 'gb_id', SelectQueryBuilder::SORT_ASC )
				->limit( $this->getBatchSize() )
				->caller( __METHOD__ )
				->fetchFieldValues();

			if ( count( $batchToProcess ) ) {
				$lastId = end( $batchToProcess );
				$firstId = reset( $batchToProcess );
				$this->output( "Now processing global blocks with id between $firstId and $lastId...\n" );

				foreach ( $batchToProcess as $id ) {
					// Check if the gb_address used by this globalblocks row is used for any other globalblocks
					// row where the gb_autoblock_parent_id is 0. If it is, then we cannot update the row with ID
					// $id because it would cause a unique index constrant violation.
					// We cannot use an UPDATE IGNORE for this as postgres will still throw an error.
					$targetForRow = $dbr->newSelectQueryBuilder()
						->select( 'gb_address' )
						->from( 'globalblocks' )
						->where( [ 'gb_id' => $id ] )
						->caller( __METHOD__ )
						->fetchField();

					$collidingRowExists = $dbw->newSelectQueryBuilder()
						->select( '1' )
						->from( 'globalblocks' )
						->where( [ 'gb_address' => $targetForRow, 'gb_autoblock_parent_id' => 0 ] )
						->fetchField();

					if ( !$collidingRowExists ) {
						$dbw->newUpdateQueryBuilder()
							->update( 'globalblocks' )
							->set( [ 'gb_autoblock_parent_id' => 0 ] )
							->where( [ 'gb_id' => $id ] )
							->caller( __METHOD__ )
							->execute();
					}

					$newValue = $dbw->newSelectQueryBuilder()
						->select( 'gb_autoblock_parent_id' )
						->from( 'globalblocks' )
						->where( [ 'gb_id' => $id ] )
						->fetchField();
					if ( $newValue !== null ) {
						$success += 1;
					} else {
						$this->output( "...Failed to update row with ID $id.\n" );
						$failed += 1;
					}
				}

				$lastProcessedRowId = end( $batchToProcess );
				$this->waitForReplication();
			}
		} while ( count( $batchToProcess ) === $this->getBatchSize() );

		$this->output( "Completed migration, updated $success row(s), failed to update $failed row(s).\n" );
		return true;
	}
}

// @codeCoverageIgnoreStart
$maintClass = UpdateAutoBlockParentIdColumn::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
