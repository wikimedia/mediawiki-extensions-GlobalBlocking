<?php

namespace MediaWiki\Extension\GlobalBlocking\Maintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\WikiMap\WikiMap;

/**
 * Maintenance script for migrating the blocker from a username to a
 * central id.
 */
class PopulateCentralId extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'GlobalBlocking' );
	}

	/**
	 * @inheritDoc
	 */
	public function getUpdateKey() {
		return 'GlobalBlockingPopulateCentralId';
	}

	/**
	 * @inheritDoc
	 */
	public function doDbUpdates() {
		$dbw = $this->getDB( DB_PRIMARY );
		$services = $this->getServiceContainer();
		$lookup = $services->getCentralIdLookup();
		$wikiId = WikiMap::getCurrentWikiId();

		$batchSize = $this->getBatchSize();
		$count = 0;
		$failed = 0;
		$lastBlock = $dbw->newSelectQueryBuilder()
			->select( 'MAX(gb_id)' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->fetchField();
		if ( !$lastBlock ) {
			$this->output( "The globalblocks table seems to be empty.\n" );
			return true;
		}

		for ( $min = 0; $min < $lastBlock; $min += $batchSize ) {
			$max = $min + $batchSize;
			$this->output( "Now processing global blocks with id between {$min} and {$max}...\n" );

			$res = $dbw->newSelectQueryBuilder()
				->select( [ 'gb_id', 'gb_by' ] )
				->from( 'globalblocks' )
				->where( [
					'gb_by_central_id' => null,
					"gb_by_wiki" => $wikiId,
					$dbw->expr( 'gb_id', '>=', $min ),
					$dbw->expr( 'gb_id', '<=', $max ),
				] )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $res as $row ) {
				$centralId = $lookup->centralIdFromName( $row->gb_by, CentralIdLookup::AUDIENCE_RAW );
				if ( $centralId === 0 ) {
					$failed++;
					continue;
				}
				$dbw->newUpdateQueryBuilder()
					->update( 'globalblocks' )
					->set( [ 'gb_by_central_id' => $centralId ] )
					->where( [ 'gb_id' => $row->gb_id ] )
					->caller( __METHOD__ )
					->execute();
			}

			$count += $dbw->affectedRows();
			$this->waitForReplication();
		}
		$this->output( "Completed migration, updated $count row(s), migration failed for $failed row(s).\n" );

		return true;
	}
}

$maintClass = PopulateCentralId::class;
require_once RUN_MAINTENANCE_IF_MAIN;
