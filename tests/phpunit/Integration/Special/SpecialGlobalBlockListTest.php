<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Specials;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockList;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use SpecialPageTestBase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockList
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager
 */
class SpecialGlobalBlockListTest extends SpecialPageTestBase {

	private static array $blockedTargets;
	private static string $globallyBlockedUser;
	private static int $autoBlockId;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	/**
	 * @inheritDoc
	 */
	protected function newSpecialPage() {
		$services = $this->getServiceContainer();
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new SpecialGlobalBlockList(
			$services->getUserNameUtils(),
			$services->getCommentFormatter(),
			$services->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalBlockDetailsRenderer()
		);
	}

	public function testViewPageBeforeSubmission() {
		// Need to get the full HTML to be able to check that the subtitle links are present
		[ $html ] = $this->executeSpecialPage( '', null, null, null, true );
		// Check that the form fields exist
		$this->assertStringContainsString( '(globalblocking-target-with-block-ids', $html );
		$this->assertStringContainsString( '(globalblocking-list-tempblocks', $html );
		$this->assertStringContainsString( '(globalblocking-list-indefblocks', $html );
		$this->assertStringContainsString( '(globalblocking-list-addressblocks', $html );
		$this->assertStringContainsString( '(globalblocking-list-rangeblocks', $html );
		// Verify that the form title is present
		$this->assertStringContainsString( '(globalblocking-search-legend', $html );
		// Verify that the special title and description are correct
		$this->assertStringContainsString( '(globalblocking-list', $html );
		$this->assertStringContainsString( '(globalblocklist-summary', $html );
		// Verify that the table headings are present
		$this->assertStringContainsString( '(globalblocking-list-table-heading-timestamp', $html );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-target', $html );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-expiry', $html );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-by', $html );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-params', $html );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-reason', $html );
		// Verify that a list of all active global blocks is shown (even though the form has not been submitted)
		foreach ( self::$blockedTargets as $target ) {
			$this->assertStringContainsString( $target, $html );
		}
		// Assert that the autoblock target is never displayed
		$this->assertStringNotContainsString( '77.8.9.11', $html );
	}

	/** @dataProvider provideTargetParam */
	public function testTargetParam( string $target, $expectedTarget ) {
		// Override the CIDR limits to allow IPv6 /18 ranges in the test.
		$this->overrideConfigValue( 'GlobalBlockingCIDRLimit', [ 'IPv4' => 16, 'IPv6' => 17 ] );
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( [ 'target' => $target ] ) );
		if ( $expectedTarget ) {
			$this->assertStringContainsString(
				$expectedTarget, $html, 'The expected block target was not shown in the page'
			);
		} else {
			$this->assertStringContainsString(
				'globalblocking-list-noresults', $html, 'Results shown when no results were expected'
			);
		}
	}

	public function provideTargetParam() {
		return [
			'single IPv4' => [ '1.2.3.4', '1.2.3.4' ],
			'exact IPv4 range' => [ '1.2.3.4/24', '1.2.3.0/24' ],
			'single IPv6' => [ '::1', '0:0:0:0:0:0:0:0/19' ],
			'exact IPv6 range' => [ '::1/19', '0:0:0:0:0:0:0:0/19' ],
			'narrower IPv6 range' => [ '::1/20', '0:0:0:0:0:0:0:0/19' ],
			'wider IPv6 range' => [ '::1/18', false ],
			'unblocked IP' => [ '6.7.8.9', false ],
			'non-existing global block ID' => [ '#123456789', false ],
			'IP that is only globally autoblocked' => [ '77.8.9.11', false ],
		];
	}

	public function testTargetParamForExistingGlobalBlockId() {
		$this->testTargetParam(
			'#' . $this->getDb()->newSelectQueryBuilder()
					->select( 'gb_id' )
					->from( 'globalblocks' )
					->where( [ 'gb_address' => '1.2.3.4' ] )
					->fetchField(),
			'1.2.3.4'
		);
	}

	public function testTargetParamWithGloballyBlockedUser() {
		$this->testTargetParam( self::$globallyBlockedUser, self::$globallyBlockedUser );
	}

	public function testTargetParamWithNonExistentUser() {
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( [ 'target' => 'NonExistentTestUser1234' ] ) );
		$this->assertStringContainsString(
			'(nosuchusershort', $html, 'The expected block target was not shown in the page'
		);
	}

	public function testIPParam() {
		// Load the page with the B/C 'ip' param for an IP that is not globally blocked and verify that the page
		// displays no results.
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( [ 'ip' => '7.6.5.4' ] ) );
		$this->assertStringContainsString(
			'globalblocking-list-noresults', $html, 'Results shown when no results were expected'
		);
	}

	/** @dataProvider provideViewPageWithOptionsSelected */
	public function testViewPageWithOptionsSelected(
		$selectedOptions, $expectedTargets, $accountIsAnExpectedTargets, $autoblockIsAnExpectedTarget
	) {
		// Add the globally blocked account to the $expectedTargets array if $accountIsAnExpectedTargets is true.
		// This is required because we do not have access to the globally blocked account name in the data provider,
		// but do once this test runs.
		if ( $accountIsAnExpectedTargets ) {
			$expectedTargets[] = self::$globallyBlockedUser;
		}
		if ( $autoblockIsAnExpectedTarget ) {
			$expectedTargets[] = '(globalblocking-global-autoblock-id: ' . self::$autoBlockId;
		}
		// Load the special page with the selected options.
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( [ 'wpOptions' => $selectedOptions ] ) );
		// Verify that the expected targets are not there.
		foreach ( $expectedTargets as $target ) {
			$this->assertStringContainsString( $target, $html );
		}
		// Assert that no other targets are listed in the page
		$targetsExpectedToNotBePresent = array_diff( self::$blockedTargets, $expectedTargets );
		foreach ( $targetsExpectedToNotBePresent as $target ) {
			$this->assertStringNotContainsString( $target, $html );
		}
		// Assert that the autoblock target is never displayed
		$this->assertStringNotContainsString( '77.8.9.11', $html );
		// If no targets are expected, verify that the no results message is shown.
		if ( count( $expectedTargets ) === 0 ) {
			$this->assertStringContainsString(
				'globalblocking-list-noresults', $html, 'Results shown when no results were expected'
			);
		}
	}

	public function provideViewPageWithOptionsSelected() {
		return [
			'Hide IP blocks' => [
				// The value of the wgOptions parameter
				[ 'addressblocks' ],
				// The targets that should appear in the special page once submitting the form.
				[ '1.2.3.0/24', '0:0:0:0:0:0:0:0/19' ],
				// Whether the globally blocked account should also be a target that appears in the special page.
				true,
				// Whether the autoblock should also be a target that appears in the results list.
				false,
			],
			'Hide range blocks' => [ [ 'rangeblocks' ], [ '1.2.3.4' ], true, true ],
			'Hide user blocks' => [ [ 'userblocks' ], [ '1.2.3.4', '1.2.3.0/24', '0:0:0:0:0:0:0:0/19' ], false, true ],
			'Hide IP and range blocks' => [ [ 'addressblocks', 'rangeblocks' ], [], true, false ],
			'Hide user, IP, and range blocks' => [ [ 'addressblocks', 'rangeblocks', 'userblocks' ], [], false, false ],
			'Hide user, IP, auto, and range blocks' => [
				[ 'addressblocks', 'rangeblocks', 'userblocks', 'autoblocks' ], [], false, false,
			],
			'Hide temporary blocks' => [ [ 'tempblocks' ], [ '1.2.3.4', '0:0:0:0:0:0:0:0/19' ], true, false ],
			'Hide indefinite blocks' => [ [ 'indefblocks' ], [ '1.2.3.0/24' ], false, true ],
			'Hide temporary and indefinite blocks' => [ [ 'tempblocks', 'indefblocks' ], [], false, false ],
		];
	}

	public function addDBDataOnce() {
		// Allow global autoblocks, so that we can check that global autoblocks are properly handled by the API
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
		// Create some testing globalblocks database rows for IPs and IP ranges for use in the above tests. These
		// should not be modified by any code in SpecialGlobalBlockList, so this can be added once per-class.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUser();
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4', 'Test reason', 'infinity', $testPerformer )
		);
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4/24', 'Test reason2', '1 month', $testPerformer )
		);
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
		// Insert an autoblock for a user block which is not the same as the user block we check for when testing.
		// This avoids test failures when the username is matched in the autoblock reason.
		$secondUserBlockStatus = $globalBlockManager->block(
			$this->getMutableTestUser()->getUserIdentity()->getName(), 'Test reason4', 'infinite',
			$testPerformer, [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $secondUserBlockStatus );
		$secondUserBlockId = $secondUserBlockStatus->getValue()['id'];
		$autoblockStatus = $globalBlockManager->autoblock( $secondUserBlockId, '77.8.9.11' );
		$this->assertStatusGood( $autoblockStatus );
		$this->assertArrayHasKey( 'id', $autoblockStatus->getValue() );
		self::$autoBlockId = $autoblockStatus->getValue()['id'];
		self::$blockedTargets = [ '1.2.3.4', '1.2.3.0/24', '0:0:0:0:0:0:0:0/19', $globallyBlockedUser ];
		self::$globallyBlockedUser = $globallyBlockedUser;
	}
}
