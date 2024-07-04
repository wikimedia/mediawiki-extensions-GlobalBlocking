<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Specials;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockList;
use MediaWiki\Request\FauxRequest;
use SpecialPageTestBase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockList
 */
class SpecialGlobalBlockListTest extends SpecialPageTestBase {

	private static array $blockedTargets;

	/**
	 * @inheritDoc
	 */
	protected function newSpecialPage() {
		$services = $this->getServiceContainer();
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new SpecialGlobalBlockList(
			$services->getBlockUtils(),
			$services->getCommentFormatter(),
			$services->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()
		);
	}

	public function testViewPageBeforeSubmission() {
		// Need to get the full HTML to be able to check that the subtitle links are present
		[ $html ] = $this->executeSpecialPage( '', null, null, null, true );
		// Check that the form fields exist
		$this->assertStringContainsString( '(globalblocking-search-ip', $html );
		$this->assertStringContainsString( '(globalblocking-list-tempblocks', $html );
		$this->assertStringContainsString( '(globalblocking-list-indefblocks', $html );
		$this->assertStringContainsString( '(globalblocking-list-addressblocks', $html );
		$this->assertStringContainsString( '(globalblocking-list-rangeblocks', $html );
		// Verify that the form title is present
		$this->assertStringContainsString( '(globalblocking-search-legend', $html );
		// Verify that the special title and description are correct
		$this->assertStringContainsString( '(globalblocking-list', $html );
		$this->assertStringContainsString( '(globalblocking-list-intro', $html );
		// Verify that a list of all active global blocks is shown (even though the form has not been submitted)
		foreach ( self::$blockedTargets as $target ) {
			$this->assertStringContainsString( $target, $html );
		}
	}

	/**
	 * @dataProvider provideIPParam
	 */
	public function testIpParam( string $ip, $expectedTarget ) {
		[ $html ] = $this->executeSpecialPage(
			'',
			new FauxRequest( [
				'ip' => $ip,
			] )
		);
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

	public function provideIPParam() {
		return [
			'single IPv4' => [ '1.2.3.4', '1.2.3.4' ],
			'exact IPv4 range' => [ '1.2.3.4/24', '1.2.3.0/24' ],
			'single IPv6' => [ '::1', '0:0:0:0:0:0:0:0/19' ],
			'exact IPv6 range' => [ '::1/19', '0:0:0:0:0:0:0:0/19' ],
			'narrower IPv6 range' => [ '::1/20', '0:0:0:0:0:0:0:0/19' ],
			'wider IPv6 range' => [ '::1/18', false ],
			'unblocked IP' => [ '6.7.8.9', false ],
		];
	}

	public function testUsernameAsSubpage() {
		[ $html ] = $this->executeSpecialPage( 'test-user' );
		$this->assertStringContainsString(
			'globalblocking-list-ipinvalid', $html, 'The form did not display the correct error for a username target'
		);
	}

	/** @dataProvider provideViewPageWithOptionsSelected */
	public function testViewPageWithOptionsSelected( $selectedOptions, $expectedTargets ) {
		// Load the special page with the selected options.
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( [ 'wpOptions' => $selectedOptions ] ) );
		// Verify that the expected targets are not there.
		foreach ( $expectedTargets as $target ) {
			$this->assertStringContainsString( $target, $html );
		}
		// Assert that no other targets are listed in the page
		$targetsExpectedToNotBePresent = array_diff( $expectedTargets, self::$blockedTargets );
		foreach ( $targetsExpectedToNotBePresent as $target ) {
			$this->assertStringNotContainsString( $target, $html );
		}
		// If no targets are expected, verify that the no results message is shown.
		if ( count( $expectedTargets ) === 0 ) {
			$this->assertStringContainsString(
				'globalblocking-list-noresults', $html, 'Results shown when no results were expected'
			);
		}
	}

	public function provideViewPageWithOptionsSelected() {
		return [
			'Hide IP blocks' => [ [ 'addressblocks' ], [ '1.2.3.0/24', '0:0:0:0:0:0:0:0/19' ] ],
			'Hide range blocks' => [ [ 'rangeblocks' ], [ '1.2.3.4' ] ],
			'Hide IP and range blocks' => [ [ 'addressblocks', 'rangeblocks' ], [] ],
			'Hide temporary blocks' => [ [ 'tempblocks' ], [ '1.2.3.4', '0:0:0:0:0:0:0:0/19' ] ],
			'Hide indefinite blocks' => [ [ 'indefblocks' ], [ '1.2.3.0/24' ] ],
			'Hide temporary and indefinite blocks' => [ [ 'tempblocks', 'indefblocks' ], [] ],
		];
	}

	public function addDBDataOnce() {
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
		self::$blockedTargets = [ '1.2.3.4', '1.2.3.0/24', '0:0:0:0:0:0:0:0/19' ];
	}
}
