<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Specials;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager
 */
class GlobalBlockListPagerTest extends MediaWikiIntegrationTestCase {

	private static UserIdentity $testPerformer;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
	}

	/** @dataProvider provideFormatRow */
	public function testFormatRow( $target, $expectedStrings, $notExpectedStrings ) {
		$this->setUserLang( 'qqx' );
		// Create a GlobalBlockListPager object to test the ::formatRow method
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$objectUnderTest = new GlobalBlockListPager(
			RequestContext::getMain(),
			[],
			$this->getServiceContainer()->getLinkRenderer(),
			$this->getServiceContainer()->getCommentFormatter(),
			$this->getServiceContainer()->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()
		);
		// Get the row data for the target
		$row = $this->getDb()->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( [ 'gb_address' => $target ] )
			->fetchRow();
		// Call the method we are testing
		$actual = $objectUnderTest->formatRow( $row );
		// Verify that the correct message is used to construct the line
		$this->assertStringContainsString( 'globalblocking-list-blockitem', $actual );
		// Verify that the username of the blocking user is present in the output
		$this->assertStringContainsString( self::$testPerformer->getName(), $actual );
		// Verify that the expected strings are present in the output
		foreach ( $expectedStrings as $expectedString ) {
			$this->assertStringContainsString( $expectedString, $actual );
		}
		// Verify that that the strings not expected are not present in the output
		foreach ( $notExpectedStrings as $notExpectedString ) {
			$this->assertStringNotContainsString( $notExpectedString, $actual );
		}
	}

	public function provideFormatRow() {
		return [
			'IPv4 block' => [
				// The target of the row in the globalblocks table that should be passed to ::formatRow
				'1.2.3.4',
				// The expected strings that should be present in the output of ::formatRow
				[
					'(globalblocking-infiniteblock', '(globalblocking-list-whitelisted',
					'Test local disable reason', '(globalblocking-list-anononly', 'Test reason1',
				],
				// Strings that are not expected to be present in the output of ::formatRow
				[ '(globalblocking-expiringblock', 'Test reason2', 'Test reason3' ],
			],
			'IPv4 range block' => [
				'1.2.3.0/24', [ '(globalblocking-expiringblock', 'Test reason2' ],
				[ '(globalblocking-infiniteblock', 'Test reason1', 'Test reason3', '(globalblocking-list-whitelisted' ],
			],
			'IPv6 range block' => [
				'0:0:0:0:0:0:0:0/19', [ '(globalblocking-infiniteblock', 'Test reason3' ],
				[ '(globalblocking-expiringblock', 'Test reason1', 'Test reason2', '(globalblocking-list-whitelisted' ],
			],
		];
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
		// Create some testing globalblocks database rows for IPs and IP ranges for use in the above tests. These
		// should not be modified by any code in GlobalBlockListPager, so this can be added once per-class.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockManager = $globalBlockingServices->getGlobalBlockManager();
		$testPerformer = $this->getTestUser( [ 'steward', 'sysop' ] )->getUser();
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4', 'Test reason1', 'infinity', $testPerformer, [ 'anon-only' ] )
		);
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4/24', 'Test reason2', '1 month', $testPerformer )
		);
		$this->assertStatusGood(
			$globalBlockManager->block( '0:0:0:0:0:0:0:0/19', 'Test reason3', 'infinite', $testPerformer )
		);
		// Insert a local disable entry to test the local disable status
		$globalBlockLocalStatusManager = $globalBlockingServices->getGlobalBlockLocalStatusManager();
		$this->assertStatusGood( $globalBlockLocalStatusManager->locallyDisableBlock(
			'1.2.3.4', 'Test local disable reason', $testPerformer
		) );
		self::$testPerformer = $testPerformer;
	}
}
