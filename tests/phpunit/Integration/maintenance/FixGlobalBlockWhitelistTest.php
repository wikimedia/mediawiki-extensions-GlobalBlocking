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
		$this->expectOutputString( "No broken whitelist entries which can be fixed.\n" );
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
				"Found 1 broken whitelist entries which can be fixed.\n" .
				" Fixed 127.0.0.1: id changed to 1\n" .
				"Finished processing broken whitelist entries.\n"
			],
			'Dry run' => [
				true,
				"Found 1 broken whitelist entries which can be fixed.\n" .
				" Whitelist broken 127.0.0.1: current gb_id is 1\n" .
				"Finished processing broken whitelist entries.\n"
			],
		];
	}

	/** @dataProvider provideExecuteForMultipleBrokenWhitelistRowsForSameTarget */
	public function testExecuteForMultipleBrokenWhitelistRowsForSameTarget( $dryRun, $expectedOutputString ) {
		// Tests that the maintenance script can handle multiple broken whitelist entries for the same target
		// by deleting all but one of the entries and fixing the remaining entry.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create a whitelist entry for the global block which will be broken
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		// Update the whitelist entry to have a different global block id to simulate a broken whitelist entry
		$this->getDb()->newUpdateQueryBuilder()
			->set( [ 'gbw_id' => 1234 ] )
			->table( 'global_block_whitelist' )
			->where( [ 'gbw_id' => 1 ] )
			->execute();
		// Call ::testExecuteWhenBrokenWhitelistRows which will add another broken entry and then
		// perform the assertions.
		$this->testExecuteWhenBrokenWhitelistRows( $dryRun, $expectedOutputString );
	}

	public static function provideExecuteForMultipleBrokenWhitelistRowsForSameTarget() {
		return [
			'Not a dry run' => [
				false,
				"Found 1 broken whitelist entries which can be fixed.\n" .
				" Deleted all whitelist entries for 127.0.0.1 except the entry with gbw_id as " .
				"1234: only one row can be updated to use id 1\n." .
				" Fixed 127.0.0.1: id changed to 1\n" .
				"Finished processing broken whitelist entries.\n"
			],
			'Dry run' => [
				true,
				"Found 1 broken whitelist entries which can be fixed.\n" .
				" Would delete all whitelist entries for 127.0.0.1 except the entry with gbw_id as " .
				"1234: only one row can be updated to use id 1\n." .
				" Whitelist broken 127.0.0.1: current gb_id is 1\n" .
				"Finished processing broken whitelist entries.\n"
			],
		];
	}

	public function testExecuteForMultipleBrokenWhitelistRows() {
		// Tests the maintenance script processing multiple broken whitelist entries for different targets, to cover
		// the code which waits for replication between batches.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create another global block
		$blockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( '127.0.0.2', 'Test block', 'infinite', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $blockStatus );
		// Create a broken whitelist entry for the global block on 127.0.0.2
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.2', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		$this->getDb()->newUpdateQueryBuilder()
			->set( [ 'gbw_id' => 12345 ] )
			->table( 'global_block_whitelist' )
			->where( [ 'gbw_id' => $blockStatus->getValue()['id'] ] )
			->execute();
		// Call ::testExecuteWhenBrokenWhitelistRows which will add another broken entry and then
		// perform the assertions.
		$this->testExecuteWhenBrokenWhitelistRows(
			false,
			"Found 2 broken whitelist entries which can be fixed.\n" .
			" Fixed 127.0.0.1: id changed to 1\n" .
			" Fixed 127.0.0.2: id changed to 2\n" .
			"Finished processing broken whitelist entries.\n"
		);
	}

	/** @dataProvider provideExecuteWhenUnbrokenWhitelistRowExists */
	public function testExecuteWhenUnbrokenWhitelistRowExists( $dryRun, $expectedOutputString ) {
		// Tests that the maintenance script can handle a deleting the broken whitelist entries if a whitelist entry
		// already exists which is not broken for that target.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Create a whitelist entry for the global block which will be broken
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
		// Update the whitelist entry to have a different global block id to simulate a broken whitelist entry
		$this->getDb()->newUpdateQueryBuilder()
			->set( [ 'gbw_id' => 1234 ] )
			->table( 'global_block_whitelist' )
			->where( [ 'gbw_id' => 1 ] )
			->execute();
		// Create a whitelist entry which is not broken
		$disableStatus = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'Test disable', $this->getTestUser()->getUser() );
		$this->assertStatusGood( $disableStatus );
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
			$dryRun ? 2 : 1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->fetchField()
		);
		$this->expectOutputString( $expectedOutputString );
	}

	public static function provideExecuteWhenUnbrokenWhitelistRowExists() {
		return [
			'Not a dry run' => [
				false,
				"Found 1 broken whitelist entries which can be fixed.\n" .
				" Deleted broken entries for 127.0.0.1: id 1 already is whitelisted.\n" .
				"Finished processing broken whitelist entries.\n"
			],
			'Dry run' => [
				true,
				"Found 1 broken whitelist entries which can be fixed.\n" .
				" Would delete broken entries for 127.0.0.1: id 1 already is whitelisted.\n" .
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
				"No broken whitelist entries which can be fixed.\n" .
				"Found 1 whitelist entries with no corresponding global blocks:\n" .
				"1.2.3.4\n" .
				"Finished deleting whitelist entries with no corresponding global blocks.\n"
			],
			'Dry run' => [
				true,
				"No broken whitelist entries which can be fixed.\n" .
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
			"No broken whitelist entries which can be fixed.\n" .
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
