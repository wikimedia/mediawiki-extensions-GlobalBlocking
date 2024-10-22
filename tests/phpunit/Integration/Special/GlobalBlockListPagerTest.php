<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Specials;

use InvalidArgumentException;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager;
use MediaWiki\MainConfigNames;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer
 */
class GlobalBlockListPagerTest extends MediaWikiIntegrationTestCase {

	use MockAuthorityTrait;

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
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalBlockDetailsRenderer()
		);
	}

	/** @dataProvider provideFormatRow */
	public function testFormatRow( $target, $expectedStrings, $notExpectedStrings ) {
		RequestContext::getMain()->setTitle( SpecialPage::getTitleFor( 'GlobalBlockList' ) );
		$objectUnderTest = $this->getObjectUnderTest();
		// Get the row data for the target
		$queryBuilder = $this->getDb()->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' );
		$globalBlockId = GlobalBlockLookup::isAGlobalBlockId( $target );
		if ( $globalBlockId ) {
			$queryBuilder->where( [ 'gb_id' => $globalBlockId ] );
		} else {
			$queryBuilder->where( [ 'gb_address' => $target ] );
		}
		$row = $queryBuilder->fetchRow();
		// Call the method we are testing
		$actual = $objectUnderTest->formatRow( $row );
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
					'(globalblocking-list-anononly', '(globalblocking-block-flag-account-creation-disabled',
					'Test reason1',
				],
				// Strings that are not expected to be present in the output of ::formatRow
				[ 'Test reason2', 'Test reason3' ],
			],
			'IPv4 range block' => [
				'1.2.3.0/24',
				[ '(july) 2136', 'Test reason2' ],
				[
					'(infiniteblock', 'Test reason1', 'Test reason3', '(globalblocking-list-whitelisted',
					'(globalblocking-block-flag-account-creation-disabled',
				],
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
		$expectedStrings = [ 'Test reason4', '(globalblocking-block-flag-account-creation-disabled' ];
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

	/** @dataProvider provideFormatRowWhenViewingUserHasActionLinks */
	public function testFormatRowWhenViewingUserHasActionLinks( $target, $expectedStrings, $notExpectedStrings ) {
		RequestContext::getMain()->setAuthority( $this->mockRegisteredUltimateAuthority() );
		RequestContext::getMain()->setTitle( SpecialPage::getTitleFor( 'GlobalBlockList' ) );
		$this->testFormatRow( $target, $expectedStrings, $notExpectedStrings );
	}

	public static function provideFormatRowWhenViewingUserHasActionLinks() {
		return [
			'IPv4 block' => [
				'1.2.3.4',
				[ '(globalblocking-list-unblock', 'Test reason1' ],
				[ 'Test reason2', 'Test reason3', 'Globally autoblocked' ],
			],
			'IPv4 autoblock' => [
				'#5',
				[ '(globalblocking-list-unblock', 'Globally autoblocked' ],
				[ '77.8.9.11' ],
			],
		];
	}

	public function testFormatValueForUnhandledName() {
		$this->expectException( InvalidArgumentException::class );
		$this->getObjectUnderTest()->formatValue( 'unknown-name', 'test' );
	}

	public function addDBDataOnce() {
		// Allow global autoblocks, so that we can check that global autoblocks are properly handled by the API
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
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
		$this->assertStatusGood( $globalBlockManager->block(
			'1.2.3.4/24', 'Test reason2', '2136-07-02', $testPerformer, [ 'allow-account-creation' ]
		) );
		$this->assertStatusGood(
			$globalBlockManager->block( '0:0:0:0:0:0:0:0/19', 'Test reason3', 'infinite', $testPerformer )
		);
		// Globally block a username to test handling global account blocks
		$globallyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();
		$userBlockStatus = $globalBlockManager->block(
			$globallyBlockedUser, 'Test reason4', 'infinite', $testPerformer,
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $userBlockStatus );
		$userBlockId = $userBlockStatus->getValue()['id'];
		// Insert an autoblock for the global block on the username target
		$autoblockStatus = $globalBlockManager->autoblock( $userBlockId, '77.8.9.11' );
		$this->assertStatusGood( $autoblockStatus );
		$this->assertArrayHasKey( 'id', $autoblockStatus->getValue() );
		// Insert a local disable entry to test the local disable status
		$globalBlockLocalStatusManager = $globalBlockingServices->getGlobalBlockLocalStatusManager();
		$this->assertStatusGood( $globalBlockLocalStatusManager->locallyDisableBlock(
			'1.2.3.4', 'Test local disable reason', $testPerformer
		) );
		self::$testPerformer = $testPerformer;
		self::$globallyBlockedUser = $globallyBlockedUser;
	}
}
