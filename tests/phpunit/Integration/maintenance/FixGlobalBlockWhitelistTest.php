<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Maintenance;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Maintenance\FixGlobalBlockWhitelist;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Maintenance\FixGlobalBlockWhitelist
 * @group Database
 */
class FixGlobalBlockWhitelistTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return FixGlobalBlockWhitelist::class;
	}

	public function testExecuteWhenNoWhitelistRows() {
		$this->maintenance->execute();
		// Expect that the maintenance script exits early if no whitelist entries are found.
		$this->expectOutputString( "No whitelist entries.\n" );
	}

	public function testExecuteWhenNoBrokenWhitelistRows() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create a whitelist entry for the global block
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		// Run the maintenance script
		$this->maintenance->execute();
		$this->expectOutputString( "All whitelist entries have corresponding global blocks.\n" );
	}

	/** @dataProvider provideExecuteWhenDeleteOptionProvided */
	public function testExecuteWhenDeleteOptionProvided( $dryRun ) {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Insert a block which will be removed after creating the whitelist entry
		$globalBlockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( '1.2.3.4', 'Test block', 'infinite', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $globalBlockStatus );
		$globalBlockId = $globalBlockStatus->getValue()['id'];
		// Create a whitelist entry which has no associated global block.
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '1.2.3.4', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		// Remove the global block on 1.2.3.4 by manually deleting the row.
		$this->getDb()->newDeleteQueryBuilder()
			->table( 'globalblocks' )
			->where( [ 'gb_address' => '1.2.3.4' ] )
			->execute();
		// Run the maintenance script
		$this->maintenance->setOption( 'dry-run', $dryRun );
		$this->maintenance->setOption( 'delete', 1 );
		$this->maintenance->execute();
		$this->assertSame(
			$dryRun ? 1 : 0,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_id' => $globalBlockId ] )
				->fetchField()
		);
		$this->expectOutputString(
			"Found 1 whitelist entries with no corresponding global blocks with IDs:\n" . "$globalBlockId\n" .
			( $dryRun ? "" : "Finished deleting whitelist entries with no corresponding global blocks.\n" )
		);
	}

	public static function provideExecuteWhenDeleteOptionProvided() {
		return [
			'Not a dry run' => [ false ],
			'Dry run' => [ true ],
		];
	}

	public function testExecuteWhenDeleteOptionProvidedButNoRowsToDelete() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create a whitelist entry for the global block
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		$globalBlockId = $disableStatus->getValue()['id'];
		// Run the maintenance script with the delete option
		$this->maintenance->setOption( 'delete', 1 );
		$this->maintenance->execute();
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_id' => $globalBlockId ] )
				->fetchField()
		);
		$this->expectOutputString( "All whitelist entries have corresponding global blocks.\n" );
	}

	public function addDBDataOnce() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Add a block to the globalblocks table
		$blockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( '127.0.0.1', 'Test block', 'infinite', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $blockStatus );
	}
}
