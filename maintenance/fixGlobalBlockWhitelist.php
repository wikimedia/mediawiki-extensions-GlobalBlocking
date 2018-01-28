<?php

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

	protected $dryRun = false;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'delete', 'Delete whitelist entries with no corresponding global block' );
		$this->addOption( 'dry-run', 'Run the script without any modifications' );
		$this->setBatchSize( 20 );

		$this->requireExtension( 'GlobalBlocking' );
	}

	public function execute() {
		$this->dryRun = $this->getOption( 'dry-run', false ) !== false;

		$db = $this->getDB( DB_REPLICA );
		$res = $db->select(
			'global_block_whitelist',
			[ 'gbw_id', 'gbw_address' ],
			[],
			__METHOD__
		);

		$whitelistEntries = [];
		foreach ( $res as $row ) {
			$whitelistEntries[ $row->gbw_id ] = $row->gbw_address;
		}

		if ( !$whitelistEntries ) {
			$this->output( "No whitelist entries.\n" );
			return;
		}

		$whitelistedIPs = array_values( $whitelistEntries );

		$gdbr = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );
		$gblocks = $gdbr->select(
			'globalblocks',
			[ 'gb_id', 'gb_address' ],
			[ 'gb_address' => $whitelistedIPs ],
			__METHOD__
		);

		$gblockEntries = [];
		foreach ( $gblocks as $gblock ) {
			$gblockEntries[ $gblock->gb_id ] = $gblock->gb_address;
		}

		$broken = [];
		foreach ( $gblockEntries as $gblockId => $gblockAddress ) {
			$whitelistId = array_search( $gblockAddress, $whitelistEntries );
			if ( $whitelistId !== false && $whitelistId !== $gblockId ) {
				$broken[ $gblockId ] = $whitelistEntries[ $whitelistId ];
			}
		}

		$brokenCount = count( $broken );
		if ( $brokenCount > 0 ) {
			$this->output( "Found $brokenCount broken whitelist entries.\n" );
			$this->fixBrokenWhitelist( $broken );
		} else {
			$this->output( "No broken whitelist entries.\n" );
		}

		if ( $this->getOption( 'delete' ) ) {
			$this->handleDeletions( array_diff( $whitelistedIPs, array_values( $gblockEntries ) ) );
		}
	}

	protected function fixBrokenWhitelist( array $brokenEntries ) {
		$count = 0;
		foreach ( $brokenEntries as $newId => $address ) {
			if ( $this->dryRun ) {
				$this->output( " Whitelist broken {$address}: current gb_id is $newId\n" );
			} else {
				$count++;
				$this->getDB( DB_MASTER )->update(
					'global_block_whitelist',
					[ 'gbw_id' => $newId ],
					[ 'gbw_address' => $address ],
					__METHOD__
				);
				$this->output( " Fixed {$address}: id changed to $newId\n" );
				if ( $count === $this->mBatchSize ) {
					wfWaitForSlaves();
					$count = 0;
				}
			}
		}
		$this->output( "Finished processing broken whitelist entries.\n" );
	}

	protected function handleDeletions( array $nonExistent ) {
		$nonExistentCount = count( $nonExistent );
		if ( $nonExistentCount > 0 ) {
			$this->output( "Found $nonExistentCount whitelist entries with no corresponding global blocks:\n"
				. implode( "\n", $nonExistent ) . "\n"
			);
			if ( !$this->dryRun ) {
				$this->getDB( DB_MASTER )->delete(
					'global_block_whitelist',
					[ 'gbw_address' => $nonExistent ],
					__METHOD__
				);
				$this->output( "Finished deleting whitelist entries with no corresponding global blocks.\n" );
			}
		} else {
			$this->output( "All whitelist entries have corresponding global blocks.\n" );
		}
	}
}

$maintClass = 'FixGlobalBlockWhitelist';
require_once RUN_MAINTENANCE_IF_MAIN;
