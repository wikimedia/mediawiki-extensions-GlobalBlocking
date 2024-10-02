<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWikiIntegrationTestCase;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager
 * @group Database
 */
class GlobalBlockLocalStatusManagerTest extends MediaWikiIntegrationTestCase {

	private static int $globalBlockId;

	public function setUp(): void {
		ConvertibleTimestamp::setFakeTime( '2021-03-02T22:00:00Z' );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	public static function provideValidTargets() {
		return [
			'Target specified via IP address' => [
				fn () => '127.0.0.1', fn () => static::$globalBlockId,
			],
			'Target specified via global block ID' => [
				fn () => '#' . static::$globalBlockId, fn () => static::$globalBlockId,
			],
		];
	}

	/** @dataProvider provideValidTargets */
	public function testLocallyDisableBlock( $targetCallback, $globalBlockIdCallback ) {
		$target = $targetCallback();
		// Call the method under test
		$performer = $this->getTestSysop()->getUser();
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( $target, 'test', $performer );
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
				'gbw_expiry' => 'infinity',
				'gbw_id' => (string)$globalBlockIdCallback(),
			],
			(array)$row,
			'The row in the global_block_whitelist table does not have the expected data.'
		);
		// Verify that the local disable caused the correct log entry
		$this->assertThatLogWasAdded(
			$target, 'whitelist',
			'Local disable log entry was not added even though the local disable was successful.'
		);
	}

	/** @dataProvider provideNotBlockedTargets */
	public function testLocallyDisableBlockOnNonexistentBlock( $target, $expectedErrorMessageKey ) {
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( $target, 'test', $this->getTestSysop()->getUser() );
		$this->assertStatusNotOK( $status, 'The returned status should be fatal.' );
		$this->assertStatusMessage(
			$expectedErrorMessageKey, $status, 'The returned status did not indicate that no block existed.'
		);
	}

	public static function provideNotBlockedTargets() {
		return [
			'Unblocked IP address' => [ '1.2.3.4', 'globalblocking-notblocked' ],
			'Global block ID which does not exist' => [ '#123456', 'globalblocking-notblocked-id' ],
		];
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
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$target = $this->getTestUser()->getUser();
		$globalBlockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( $target, 'test', 'infinite', $this->getTestSysop()->getUser() );
		$this->assertStatusGood( $globalBlockStatus );
		$globalBlockId = $globalBlockStatus->getValue()['id'];
		$status = $globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( $target, 'test', $this->getTestSysop()->getUser() );
		$this->assertStatusGood( $status, 'The returned status should be good.' );
		$this->assertNotFalse(
			$this->getDb()->newSelectQueryBuilder()
				->select( '1' )
				->from( 'global_block_whitelist' )
				->where( [ 'gbw_id' => $globalBlockId ] )
				->fetchField(),
			'The GlobalBlockLocalStatusManager did not disable the correct block.'
		);
	}

	/** @dataProvider provideValidTargets */
	public function testLocallyEnableBlock( $targetCallback ) {
		$target = $targetCallback();
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Disable the block on 127.0.0.1 so that we can re-enable it
		$testSysop = $this->getTestSysop()->getUser();
		$globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( $target, 'test', $testSysop );
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
			->locallyEnableBlock( $target, 'test', $performer );
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
			$target, 'dwhitelist',
			'Local enable log entry was not added even though the local enable was successful.'
		);
	}

	/** @dataProvider provideNotBlockedTargets */
	public function testLocallyEnableBlockOnNonexistentBlock( $target, $expectedErrorMessageKey ) {
		$status = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLocalStatusManager()
			->locallyEnableBlock( $target, 'test', $this->getTestSysop()->getUser() );
		$this->assertStatusNotOK( $status, 'The returned status should be fatal.' );
		$this->assertStatusMessage(
			$expectedErrorMessageKey, $status, 'The returned status did not indicate that no block existed.'
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

	private function getTargetForLogEntry( string $target ): LinkTarget {
		// We need to use TitleValue::tryNew for block IDs, as the block ID contains a "#" character which
		// causes the title to be rejected by Title::makeTitleSafe. In all other cases we can use Title::makeTitleSafe.
		if ( GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			return TitleValue::tryNew( NS_USER, $target );
		} else {
			return Title::makeTitleSafe( NS_USER, $target );
		}
	}

	private function assertThatLogWasAdded( $target, $action, $failMessage ) {
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'logging' )
				->where( [
					'log_type' => 'gblblock', 'log_action' => $action, 'log_namespace' => NS_USER,
					'log_title' => $this->getTargetForLogEntry( $target )->getDBkey(),
				] )
				->caller( __METHOD__ )
				->fetchField(),
			$failMessage
		);
	}

	public function addDBDataOnce() {
		// Add a block to the database to test with
		$performer = $this->getTestUser( [ 'steward', 'sysop' ] )->getUser();
		$globalBlockStatus = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager()
			->block( '127.0.0.1', 'test', 'infinite', $performer );
		$this->assertStatusGood( $globalBlockStatus );
		self::$globalBlockId = $globalBlockStatus->getValue()['id'];
	}
}
