<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Maintenance;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Maintenance\UpdateAutoBlockParentIdColumn;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikimedia\Rdbms\IMaintainableDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Maintenance\UpdateAutoBlockParentIdColumn
 * @group Database
 */
class UpdateAutoBlockParentIdColumnTest extends MaintenanceBaseTestCase {

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	protected function getMaintenanceClass() {
		return UpdateAutoBlockParentIdColumn::class;
	}

	public function testExecuteWhenNoGlobalBlockTableRows() {
		$this->maintenance->execute();
		$this->expectOutputString( "The globalblocks table has no rows to update.\n" );
	}

	private function getTestingGlobalBlock( string $target ): int {
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$globalBlockStatus = $globalBlockManager->block(
			$target, 'Test', 'infinity', $this->getTestUser( [ 'steward' ] )->getUserIdentity()
		);
		$this->assertStatusGood( $globalBlockStatus );
		return $globalBlockStatus->getValue()['id'];
	}

	public function testExecuteWhenNoGlobalBlockRowsWithNullAutoblockParentId() {
		// Create a global block using the GlobalBlockManager service, which sets gb_autoblock_parent_id to an integer
		// and never NULL.
		$this->getTestingGlobalBlock( '1.2.3.4' );
		$this->maintenance->execute();
		$this->expectOutputString( "The globalblocks table has no rows to update.\n" );
		// Check that the value of gb_autoblock_parent_id was not modified by the script, as there were no updates
		// to perform to it.
		$this->newSelectQueryBuilder()
			->select( 'gb_autoblock_parent_id' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValue( '0' );
	}

	private function assertOnGlobalBlockingRows( $expectedRows ) {
		$this->newSelectQueryBuilder()
			->select( [ 'gb_id', 'gb_autoblock_parent_id' ] )
			->from( 'globalblocks' )
			->orderBy( 'gb_id', SelectQueryBuilder::SORT_ASC )
			->assertResultSet( $expectedRows );
	}

	public function testExecute() {
		// The schema change for SQLite to allow NULL in gb_autoblock_parent_id is too brittle, so skip tests for
		// SQLite. The schema change for postgres does not work, so skip that too.
		$this->markTestSkippedIfDbType( 'sqlite' );
		$this->markTestSkippedIfDbType( 'postgres' );

		$firstGlobalBlockId = $this->getTestingGlobalBlock( '1.2.3.4' );
		$secondGlobalBlockId = $this->getTestingGlobalBlock( '1.2.3.5' );
		$thirdGlobalBlockId = $this->getTestingGlobalBlock( $this->getTestUser()->getUserIdentity()->getName() );
		$fourthGlobalBlockId = $this->getTestingGlobalBlock( '1.2.3.7' );

		// Set the first, second, and fourth globalblocks rows to have gb_autoblock_parent_id as NULL
		$this->getDb()->newUpdateQueryBuilder()
			->update( 'globalblocks' )
			->set( [ 'gb_autoblock_parent_id' => null ] )
			->where( [ 'gb_id' => [ $firstGlobalBlockId, $secondGlobalBlockId, $fourthGlobalBlockId ] ] )
			->caller( __METHOD__ )
			->execute();

		// Check that the DB is correctly set up for the test.
		$this->assertOnGlobalBlockingRows( [
			[ $firstGlobalBlockId, null ], [ $secondGlobalBlockId, null ], [ $thirdGlobalBlockId, 0 ],
			[ $fourthGlobalBlockId, null ],
		] );

		// Run the maintenance script and check that the first two global blocks were correctly updated
		$this->maintenance->loadWithArgv( [ '--batch-size', 2 ] );
		$this->maintenance->execute();
		$this->expectOutputString(
			"Now processing global blocks with id between 1 and 2...\n" .
			"Now processing global blocks with id between 4 and 4...\n" .
			"Completed migration, updated 3 row(s), failed to update 0 row(s).\n"
		);

		// Check that the DB is correct after the maintenance script execution.
		$this->assertOnGlobalBlockingRows( [
			[ $firstGlobalBlockId, 0 ], [ $secondGlobalBlockId, 0 ], [ $thirdGlobalBlockId, 0 ],
			[ $fourthGlobalBlockId, 0 ],
		] );
	}

	public function testExecuteWithCollidingRows() {
		// The schema change for SQLite to allow NULL in gb_autoblock_parent_id is too brittle, so skip tests for
		// SQLite. The schema change for postgres does not work, so skip that too.
		$this->markTestSkippedIfDbType( 'sqlite' );
		$this->markTestSkippedIfDbType( 'postgres' );

		// Get two globalblocks rows with the same target and gb_autoblock_parent_id as NULL
		$firstGlobalBlockId = $this->getTestingGlobalBlock( '1.2.3.4' );
		$secondGlobalBlockId = $this->getTestingGlobalBlock( '1.2.3.7' );
		$this->getDb()->newUpdateQueryBuilder()
			->update( 'globalblocks' )
			->set( [ 'gb_autoblock_parent_id' => null ] )
			->where( [ 'gb_id' => [ $firstGlobalBlockId, $secondGlobalBlockId ] ] )
			->caller( __METHOD__ )
			->execute();
		$this->getDb()->newUpdateQueryBuilder()
			->update( 'globalblocks' )
			->set( [ 'gb_address' => '1.2.3.4' ] )
			->where( [ 'gb_id' => $secondGlobalBlockId ] )
			->caller( __METHOD__ )
			->execute();

		// Get a globalblocks row that needs updating but does not conflict
		$thirdGlobalBlockId = $this->getTestingGlobalBlock( '1.2.3.8' );
		$this->getDb()->newUpdateQueryBuilder()
			->update( 'globalblocks' )
			->set( [ 'gb_autoblock_parent_id' => null ] )
			->where( [ 'gb_id' => $thirdGlobalBlockId ] )
			->caller( __METHOD__ )
			->execute();

		// Check that the DB is correctly set up for the test.
		$this->newSelectQueryBuilder()
			->select( [ 'gb_id', 'gb_autoblock_parent_id', 'gb_address' ] )
			->from( 'globalblocks' )
			->orderBy( 'gb_id', SelectQueryBuilder::SORT_ASC )
			->assertResultSet( [
				[ $firstGlobalBlockId, null, '1.2.3.4' ], [ $secondGlobalBlockId, null, '1.2.3.4' ],
				[ $thirdGlobalBlockId, null, '1.2.3.8' ],
			] );

		// Run the maintenance script and check that the first global block was updated, the second failed to be
		// updated as it collides with the first, and the third successfully is updated.
		$this->maintenance->execute();
		$this->expectOutputString(
			"Now processing global blocks with id between 1 and 3...\n" .
			"...Failed to update row with ID 2.\n" .
			"Completed migration, updated 2 row(s), failed to update 1 row(s).\n"
		);

		$this->newSelectQueryBuilder()
			->select( [ 'gb_id', 'gb_autoblock_parent_id' ] )
			->from( 'globalblocks' )
			->orderBy( 'gb_id', SelectQueryBuilder::SORT_ASC )
			->assertResultSet( [
				[ $firstGlobalBlockId, 0 ], [ $secondGlobalBlockId, null ], [ $thirdGlobalBlockId, 0 ],
			] );
	}

	protected function getSchemaOverrides( IMaintainableDatabase $db ) {
		if ( $db->getType() !== 'mysql' ) {
			return [];
		}
		return [
			'scripts' => [
				__DIR__ . '/patches/' . $db->getType() .
				'/patch-globalblocks-change-gb_autoblock_parent_id-null-default.sql',
			],
			'drop' => [],
			'create' => [],
			'alter' => [ 'globalblocks' ],
		];
	}
}
