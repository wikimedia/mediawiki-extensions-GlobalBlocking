<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Specials;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager;
use MediaWiki\MainConfigNames;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager
 */
class GlobalBlockListPagerTest extends MediaWikiIntegrationTestCase {

	private static UserIdentity $testPerformer;
	private static string $globallyBlockedUser;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	private function getObjectUnderTest() {
		$this->setUserLang( 'qqx' );
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		return new GlobalBlockListPager(
			RequestContext::getMain(),
			[],
			$this->getServiceContainer()->getLinkRenderer(),
			$this->getServiceContainer()->getCommentFormatter(),
			$this->getServiceContainer()->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalBlockLocalStatusLookup(),
			$this->getServiceContainer()->getUserIdentityLookup(),
			$globalBlockingServices->getGlobalBlockingUserVisibilityLookup()
		);
	}

	/** @dataProvider provideFormatRow */
	public function testFormatRow( $target, $expectedStrings, $notExpectedStrings ) {
		$objectUnderTest = $this->getObjectUnderTest();
		// Get the row data for the target
		$row = $this->getDb()->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( [ 'gb_address' => $target ] )
			->fetchRow();
		// Call the method we are testing
		$actual = $objectUnderTest->formatRow( $row );
		// Verify that the correct message is used to construct the line
		$this->assertStringContainsString( 'globalblocking-list-item', $actual );
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
					'(infiniteblock', '(globalblocking-list-whitelisted', 'Test local disable reason',
					'(globalblocking-list-anononly', 'Test reason1',
				],
				// Strings that are not expected to be present in the output of ::formatRow
				[ 'Test reason2', 'Test reason3' ],
			],
			'IPv4 range block' => [
				'1.2.3.0/24',
				// The "july" message is only used for the expiry, so if it is present then the expiry is there.
				[ '(july', 'Test reason2' ],
				[ '(infiniteblock', 'Test reason1', 'Test reason3', '(globalblocking-list-whitelisted' ],
			],
			'IPv6 range block' => [
				'0:0:0:0:0:0:0:0/19', [ '(infiniteblock', 'Test reason3' ],
				[ 'Test reason1', 'Test reason2', '(globalblocking-list-whitelisted' ],
			],
		];
	}

	/** @dataProvider provideFormatRowForGloballyBlockedUser */
	public function testFormatRowForGloballyBlockedUser( $targetUserIsHidden ) {
		if ( $targetUserIsHidden ) {
			// If the globally blocked user should be hidden from the current authority, then hide the user by
			// blocking it locally with 'isHideUser' set to true.
			$blockStatus = $this->getServiceContainer()->getBlockUserFactory()
				->newBlockUser(
					self::$globallyBlockedUser, $this->getTestUser( [ 'sysop', 'suppress' ] )->getUser(), 'infinity',
					'block to hide the test user', [ 'isHideUser' => true ]
				)->placeBlock();
			$this->assertStatusGood( $blockStatus );
		}
		$expectedStrings = [ 'Test reason4' ];
		$notExpectedStrings = [ 'Test reason1', 'Test reason2', 'Test reason3' ];
		if ( $targetUserIsHidden ) {
			// If the globally blocked user should be hidden from the current authority, then the username of the
			// globally blocked user should be not present in the page.
			$notExpectedStrings[] = self::$globallyBlockedUser;
			$expectedStrings[] = '(rev-deleted-user';
		} else {
			// If the globally blocked user is not hidden, then it should be present in the page.
			$expectedStrings[] = self::$globallyBlockedUser;
			$notExpectedStrings[] = '(rev-deleted-user';
		}
		$this->testFormatRow( self::$globallyBlockedUser, $expectedStrings, $notExpectedStrings );
	}

	public static function provideFormatRowForGloballyBlockedUser() {
		return [
			'Globally blocked user is hidden from the current user' => [ true ],
			'Globally blocked user is not hidden from the current user' => [ false ],
		];
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
		// Create some testing globalblocks database rows for IPs, IP ranges, and accounts for use in the above tests.
		// These should not be modified by any code in GlobalBlockListPager, so this can be added once per-class.
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
		$globallyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();
		$this->assertStatusGood(
			$globalBlockManager->block( $globallyBlockedUser, 'Test reason4', 'infinite', $testPerformer )
		);
		// Insert a local disable entry to test the local disable status
		$globalBlockLocalStatusManager = $globalBlockingServices->getGlobalBlockLocalStatusManager();
		$this->assertStatusGood( $globalBlockLocalStatusManager->locallyDisableBlock(
			'1.2.3.4', 'Test local disable reason', $testPerformer
		) );
		self::$testPerformer = $testPerformer;
		self::$globallyBlockedUser = $globallyBlockedUser;
	}
}
