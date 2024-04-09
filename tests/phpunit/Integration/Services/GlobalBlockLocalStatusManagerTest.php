<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager
 * @group Database
 */
class GlobalBlockLocalStatusManagerTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		ConvertibleTimestamp::setFakeTime( '2021-03-02T22:00:00Z' );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
	}

	public function testLocallyDisableBlock() {
		// Call the method under test
		$performer = $this->getTestSysop()->getUser();
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'test', $performer );
		$this->assertStatusGood( $status, 'The returned status should be good.' );
		// Verify that the global_block_whitelist table has one row.
		$row = $this->getDb()->newSelectQueryBuilder()
			->select( '*' )
			->from( 'global_block_whitelist' )
			->fetchRow();
		$this->assertNotFalse( $row, 'The global_block_whitelist table should have one row.' );
		// Verify that the row has the expected data
		$this->assertArraySubmapSame(
			[
				'gbw_by' => (string)$performer->getId(),
				'gbw_by_text' => $performer->getName(),
				'gbw_address' => '127.0.0.1',
				'gbw_expiry' => 'infinity',
				'gbw_target_central_id' => '0',
				'gbw_id' => $this->getDb()->newSelectQueryBuilder()
					->select( 'gb_id' )
					->from( 'globalblocks' )
					->where( [ 'gb_address' => '127.0.0.1' ] )
					->fetchField(),
			],
			(array)$row,
			'The row in the global_block_whitelist table does not have the expected data.'
		);
		// Verify that the local disable caused the correct log entry
		$this->assertThatLogWasAdded(
			'127.0.0.1', 'whitelist',
			'Local disable log entry was not added even though the local disable was successful.'
		);
	}

	public function testLocallyDisableBlockOnNonexistentBlock() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', true );
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '1.2.3.4', 'test', $this->getTestSysop()->getUser() );
		$this->assertStatusNotOK( $status, 'The returned status should be fatal.' );
		$this->assertStatusMessage(
			'globalblocking-notblocked-new', $status,
			'The returned status did not indicate that no block existed.'
		);
	}

	public function testLocallyDisableBlockOnAlreadyDisabled() {
		// Call the method under test twice and assert on the second status
		$testSysop = $this->getTestSysop()->getUser();
		GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'test', $testSysop );
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'test', $testSysop );
		$this->assertStatusNotOK( $status, 'The returned status should be fatal.' );
		$this->assertStatusMessage(
			'globalblocking-whitelist-nochange', $status,
			'The returned status did not indicate that the block was already locally disabled.'
		);
	}

	public function testLocallyDisableBlockForUser() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', true );
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$target = $this->getTestUser()->getUser();
		$globalBlockingServices->getGlobalBlockManager()
			->block( $target, 'test', 'infinite', $this->getTestSysop()->getUser() );
		$status = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( $target, 'test', $this->getTestSysop()->getUser() );
		$this->assertStatusGood( $status, 'The returned status should be good.' );
		$this->assertSame(
			$this->getServiceContainer()->getCentralIdLookup()->centralIdFromName( $target->getName() ),
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'gbw_target_central_id' )
				->from( 'global_block_whitelist' )
				->fetchField(),
			'The global_block_whitelist table did not save the correct central ID.'
		);
	}

	public function testLocallyEnableBlock() {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Disable the block on 127.0.0.1 so that we can re-enable it
		$testSysop = $this->getTestSysop()->getUser();
		$globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '127.0.0.1', 'test', $testSysop );
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->fetchField(),
			'The global_block_whitelist table should have one row for the test to work.'
		);
		// Call the method under test to re-enable the block.
		$performer = $this->getTestSysop()->getUser();
		$status = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyEnableBlock( '127.0.0.1', 'test', $performer );
		$this->assertStatusGood( $status, 'The returned status should be good.' );
		// Verify that the global_block_whitelist table has no rows.
		$this->assertSame(
			0,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->fetchField(),
			'The global_block_whitelist table should have no rows after re-enabling the block.'
		);
		// Verify that the local enable caused the correct log entry
		$this->assertThatLogWasAdded(
			'127.0.0.1', 'dwhitelist',
			'Local enable log entry was not added even though the local enable was successful.'
		);
	}

	public function testLocallyEnableBlockOnNonexistentBlock() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', true );
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyEnableBlock( '1.2.3.4', 'test', $this->getTestSysop()->getUser() );
		$this->assertStatusNotOK( $status, 'The returned status should be fatal.' );
		$this->assertStatusMessage(
			'globalblocking-notblocked-new', $status,
			'The returned status did not indicate that no block existed.'
		);
	}

	public function testLocallyEnableBlockOnAlreadyEnabled() {
		$testSysop = $this->getTestSysop()->getUser();
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyEnableBlock( '127.0.0.1', 'test', $testSysop );
		$this->assertStatusNotOK( $status, 'The returned status should be fatal.' );
		$this->assertStatusMessage(
			'globalblocking-whitelist-nochange', $status,
			'The returned status did not indicate that the block was already locally enabled.'
		);
	}

	private function assertThatLogWasAdded( $target, $action, $failMessage ) {
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'logging' )
				->where( [
					'log_type' => 'gblblock', 'log_action' => $action, 'log_namespace' => NS_USER,
					'log_title' => Title::makeTitleSafe( NS_USER, $target )->getDBkey(),
				] )
				->caller( __METHOD__ )
				->fetchField(),
			$failMessage
		);
	}

	public function addDBDataOnce() {
		// Add a block to the database to test with
		$performer = $this->getTestUser( [ 'steward', 'sysop' ] )->getUser();
		GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager()
			->block( '127.0.0.1', 'test', 'infinite', $performer );
	}
}
