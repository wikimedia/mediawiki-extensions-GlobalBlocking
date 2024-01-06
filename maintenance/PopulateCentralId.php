<?php

namespace MediaWiki\Extension\GlobalBlocking\Maintenance;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use LoggedUpdateMaintenance;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\MediaWikiServices;
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
		$dbr = GlobalBlocking::getReplicaGlobalBlockingDatabase();
		$dbw = GlobalBlocking::getPrimaryGlobalBlockingDatabase();
		$services = MediaWikiServices::getInstance();
		$lbFactory = $services->getDBLoadBalancerFactory();
		$lookup = $services->getCentralIdLookup();
		$wikiId = WikiMap::getCurrentWikiId();

		$batchSize = $this->getBatchSize();
		$count = 0;
		$failed = 0;
		$lastBlock = $dbr->selectField( 'globalblocks', 'MAX(gb_id)', '', __METHOD__ );
		if ( !$lastBlock ) {
			$this->output( "The globalblocks table seems to be empty.\n" );
			return true;
		}

		for ( $min = 0; $min < $lastBlock; $min += $batchSize ) {
			$max = $min + $batchSize;
			$this->output( "Now processing global blocks with id between {$min} and {$max}...\n" );

			$res = $dbr->select(
				'globalblocks',
				[ 'gb_id', 'gb_by' ],
				[
					'gb_by_central_id' => null,
					"gb_by_wiki" => $wikiId,
					"gb_id BETWEEN $min AND $max"
				],
				__METHOD__
			);

			foreach ( $res as $row ) {
				$centralId = $lookup->centralIdFromName( $row->gb_by, CentralIdLookup::AUDIENCE_RAW );
				if ( $centralId === 0 ) {
					$failed++;
					continue;
				}
				$dbw->update(
					'globalblocks',
					[ 'gb_by_central_id' => $centralId ],
					[ 'gb_id' => $row->gb_id ],
					__METHOD__
				);
			}

			$count += $dbw->affectedRows();
			$lbFactory->waitForReplication();
		}
		$this->output( "Completed migration, updated $count row(s), migration failed for $failed row(s).\n" );

		return true;
	}
}

$maintClass = PopulateCentralId::class;
require_once RUN_MAINTENANCE_IF_MAIN;
