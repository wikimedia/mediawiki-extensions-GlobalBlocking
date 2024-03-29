<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Maintenance;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Maintenance\FixGlobalBlockWhitelist;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikimedia\TestingAccessWrapper;

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
		$this->expectOutputString( "No broken whitelist entries.\n" );
	}

	/** @dataProvider provideExecuteWhenBrokenWhitelistRows */
	public function testExecuteWhenBrokenWhitelistRows( $dryRun, $expectedOutputString ) {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create a whitelist entry for the global block
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		// Update the whitelist entry to have a different global block id to simulate a broken whitelist entry
		$this->getDb()->newUpdateQueryBuilder()
			->set( [ 'gbw_id' => 123 ] )
			->table( 'global_block_whitelist' )
			->where( [ 'gbw_id' => 1 ] )
			->execute();
		// Set the batch size to 1 to test the code that waits for replication.
		// A copy of the maintenance property is used because IDEs flag the access
		// to the protected method as an error.
		/** @var TestingAccessWrapper $maintenance */
		$maintenance = $this->maintenance;
		$maintenance->setBatchSize( 1 );
		$this->maintenance = $maintenance;
		// Run the maintenance script
		$this->maintenance->setOption( 'dry-run', $dryRun );
		$this->maintenance->execute();
		$this->assertSame(
			$dryRun ? 0 : 1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_id' => 1 ] )
				->fetchField()
		);
		$this->expectOutputString( $expectedOutputString );
	}

	public static function provideExecuteWhenBrokenWhitelistRows() {
		return [
			'Not a dry run' => [
				false,
				"Found 1 broken whitelist entries.\n" .
				" Fixed 127.0.0.1: id changed to 1\n" .
				"Finished processing broken whitelist entries.\n"
			],
			'Dry run' => [
				true,
				"Found 1 broken whitelist entries.\n" .
				" Whitelist broken 127.0.0.1: current gb_id is 1\n" .
				"Finished processing broken whitelist entries.\n"
			],
		];
	}

	/** @dataProvider provideExecuteWhenDeleteOptionProvided */
	public function testExecuteWhenDeleteOptionProvided( $dryRun, $expectedOutputString ) {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Insert a block which will be removed after creating the whitelist entry
		$globalBlockingServices->getGlobalBlockManager()
			->block( '1.2.3.4', 'Test block', 'infinite', $this->getTestUser()->getUser() );
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
				->where( [ 'gbw_address' => '1.2.3.4' ] )
				->fetchField()
		);
		$this->expectOutputString( $expectedOutputString );
	}

	public static function provideExecuteWhenDeleteOptionProvided() {
		return [
			'Not a dry run' => [
				false,
				"No broken whitelist entries.\n" .
				"Found 1 whitelist entries with no corresponding global blocks:\n" .
				"1.2.3.4\n" .
				"Finished deleting whitelist entries with no corresponding global blocks.\n"
			],
			'Dry run' => [
				true,
				"No broken whitelist entries.\n" .
				"Found 1 whitelist entries with no corresponding global blocks:\n" .
				"1.2.3.4\n"
			],
		];
	}

	public function testExecuteWhenDeleteOptionProvidedButNoRowsToDelete() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create a whitelist entry for the global block
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		// Run the maintenance script with the delete option
		$this->maintenance->setOption( 'delete', 1 );
		$this->maintenance->execute();
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_address' => '127.0.0.1' ] )
				->fetchField()
		);
		$this->expectOutputString(
			"No broken whitelist entries.\n" .
			"All whitelist entries have corresponding global blocks.\n"
		);
	}

	public function addDBDataOnce() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Add a blocks to the globalblocks table
		$blockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( '127.0.0.1', 'Test block', 'infinite', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $blockStatus );
	}
}
