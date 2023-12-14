<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\MediaWikiServices;

/**
 * Maintenance script for updating the username of a blocker in globalblocks.
 *
 * See https://phabricator.wikimedia.org/T298707.
 */
class FixBlockerUsername extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'GlobalBlocking' );
		$this->setBatchSize( 1000 );

		$this->addArg( 'oldname', 'Username of the blocker that should be updated', true );
		$this->addArg( 'newname', 'The username it should be updated to', true );
	}

	public function execute() {
		$oldname = $this->getArg( 0 );
		$newname = $this->getArg( 1 );
		$dbw = GlobalBlocking::getPrimaryGlobalBlockingDatabase();
		$services = MediaWikiServices::getInstance();
		$lbFactory = $services->getDBLoadBalancerFactory();

		$lastBlock = $dbw->selectField( 'globalblocks', 'MAX(gb_id)', '', __METHOD__ );

		for ( $min = 0; $min <= $lastBlock; $min += $this->getBatchSize() ) {
			$max = $min + $this->getBatchSize();
			$this->output( "Now processing global blocks with id between {$min} and {$max}...\n" );

			$dbw->update(
				'globalblocks',
				[ 'gb_by' => $newname ],
				[
					'gb_by' => $oldname,
					'gb_id BETWEEN ' . $min . ' AND ' . $max
				],
				__METHOD__
			);

			$lbFactory->waitForReplication();
		}
		$this->output( "Updated Blocks made by {$oldname}.\n" );
	}
}

$maintClass = FixBlockerUsername::class;
require_once RUN_MAINTENANCE_IF_MAIN;
