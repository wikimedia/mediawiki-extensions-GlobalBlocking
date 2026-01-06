<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use MediaWiki\Context\RequestContext;
use MediaWiki\Exception\PermissionsError;
use MediaWiki\Exception\ReadOnlyError;
use MediaWiki\Exception\UserBlockedError;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\User;
use SpecialPageTestBase;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialMassGlobalBlock
 * @group Database
 */
class SpecialMassGlobalBlockTest extends SpecialPageTestBase {

	use MockAuthorityTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValues( [
			// We don't want to test specifically the CentralAuth implementation
			// of the CentralIdLookup. As such, force it to be the local provider.
			MainConfigNames::CentralIdLookupProvider => 'local',
			// Don't test multiblocks by default
			MainConfigNames::EnableMultiBlocks => false,
		] );
	}

	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'MassGlobalBlock' );
	}

	public function testViewSpecialPageForUserWithoutNecessaryRight() {
		$this->expectException( PermissionsError::class );
		$this->executeSpecialPage();
	}

	public function testViewSpecialPageForBlockedUser() {
		// Get a user which is blocked but has the rights to see the page if they were not blocked.
		$performer = $this->getMutableTestUser( [ 'steward' ] )->getUser();
		$blockStatus = $this->getServiceContainer()->getBlockUserFactory()
			->newBlockUser( $performer->getName(), $this->mockRegisteredUltimateAuthority(), 'indefinite' )
			->placeBlock();
		$this->assertStatusGood( $blockStatus );
		// Expect that the blocked user cannot see the special page is they are blocked.
		$this->expectException( UserBlockedError::class );
		$this->executeSpecialPage( '', null, null, $performer );
	}

	private function getUserForSuccess( array $additionalGroups = [] ): User {
		return $this->getTestUser( array_merge( [ 'steward' ], $additionalGroups ) )->getUser();
	}

	public function testViewSpecialPageWhenInReadOnlyMode() {
		$this->getServiceContainer()->getReadOnlyMode()->setReason( 'testing' );
		$this->expectException( ReadOnlyError::class );
		$this->executeSpecialPage( '', new FauxRequest(), null, $this->getUserForSuccess() );
	}

	/**
	 * Calls DOMCompat::getElementById, expects that it returns a valid Element object and then returns
	 * the HTML of that Element.
	 *
	 * @param string $html The HTML to search through
	 * @param string $id The ID to search for, excluding the "#" character
	 * @return string
	 */
	private function assertAndGetByElementId( string $html, string $id ): string {
		$specialPageDocument = DOMUtils::parseHTML( $html );
		$element = DOMCompat::getElementById( $specialPageDocument, $id );
		$this->assertNotNull( $element, "Could not find element with ID $id in $html" );
		return DOMCompat::getOuterHTML( $element );
	}

	private function verifyQueryFormPresent( string $html ) {
		// Check that the query form is present on the page, along with the form fields.
		$queryFormHtml = $this->assertAndGetByElementId( $html, 'mw-globalblocking-mass-block-query' );
		$this->assertStringContainsString( '(globalblocking-mass-block-query-legend', $queryFormHtml );
		$this->assertStringContainsString( '(globalblocking-mass-block-query-submit', $queryFormHtml );
		$this->assertStringContainsString( '(globalblocking-mass-block-query-placeholder', $queryFormHtml );
		$this->assertStringContainsString( 'mw-globalblocking-mass-block-query', $queryFormHtml );
		$this->assertStringContainsString( 'mw-globalblock-addresslist', $queryFormHtml );
		// Return the HTML of the query form for further assertions.
		return $queryFormHtml;
	}

	public function testViewSpecialPage() {
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest(), null, $this->getUserForSuccess(), true );
		$this->verifyQueryFormPresent( $html );
		// Check that the summary is present
		$this->assertStringContainsString( '(massglobalblock-summary)', $html );
		// Check that the subtitle is present, with the links to the other special pages
		$this->assertStringContainsString( '(globalblocking-goto-block', $html );
		// Check that the block form is not shown, as we have not queried for any users yet
		$this->assertStringNotContainsString( '(globalblocking-mass-block-legend', $html );
		$this->assertStringNotContainsString( 'mw-globalblocking-mass-block-table', $html );
	}

	/**
	 * Helper method used to expect that one element matches the given selector inside the given parent element.
	 *
	 * @param string $html The HTML to search through
	 * @param string $selector The CSS selector which should match only one element
	 * @return string
	 */
	private function getAndExpectSingleMatchingElement( string $html, string $selector ): string {
		$htmlElement = DOMUtils::parseHTML( $html );
		$matchingElement = DOMCompat::querySelectorAll( $htmlElement, $selector );
		$this->assertCount( 1, $matchingElement, "One element was expected to match $selector" );
		return DOMCompat::getInnerHTML( $matchingElement[0] );
	}

	private function verifyMassGlobalBlockTableShown( string $html ): string {
		$queryResultTableHtml = $this->assertAndGetByElementId( $html, 'mw-globalblocking-mass-block-table' );
		// Verify that the table headings are all present
		$this->assertStringContainsString( '(globalblocking-list-table-heading-target', $queryResultTableHtml );
		$this->assertStringContainsString( '(globalblocking-mass-block-header-status', $queryResultTableHtml );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-expiry', $queryResultTableHtml );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-params', $queryResultTableHtml );
		// Return the HTML of the table for further assertions.
		return $queryResultTableHtml;
	}

	private function verifyMassGlobalBlockFormFieldsShown( string $html, bool $viewerHasSysopGroup ) {
		// Verify that the form fields for global blocking / unblocking are shown
		$this->assertStringContainsString( '(globalblocking-mass-block-block', $html );
		$this->assertStringContainsString( '(globalblocking-mass-block-unblock', $html );
		$this->assertStringContainsString( '(globalblocking-block-expiry', $html );
		$this->assertStringContainsString( '(globalblocking-block-reason', $html );
		$this->assertStringContainsString( '(globalblocking-ipbanononly', $html );
		$this->assertStringContainsString( '(globalblocking-block-disable-account-creation', $html );
		$this->assertStringContainsString( '(globalblocking-block-block-email', $html );
		$this->assertStringContainsString( '(globalblocking-block-enable-autoblock', $html );
		$this->assertStringContainsString( '(globalblocking-mass-block-bot', $html );
		if ( $viewerHasSysopGroup ) {
			$this->assertStringContainsString( '(globalblocking-also-local', $html );
			$this->assertStringContainsString( '(globalblocking-also-local-talk', $html );
			$this->assertStringContainsString( '(globalblocking-also-local-email', $html );
			$this->assertStringContainsString( '(globalblocking-also-local-soft', $html );
			$this->assertStringContainsString( '(globalblocking-also-local-disable-account-creation', $html );
		} else {
			$this->assertStringNotContainsString( '(globalblocking-also-local', $html );
			$this->assertStringNotContainsString( '(globalblocking-also-local-talk', $html );
			$this->assertStringNotContainsString( '(globalblocking-also-local-email', $html );
			$this->assertStringNotContainsString( '(globalblocking-also-local-soft', $html );
			$this->assertStringNotContainsString( '(globalblocking-also-local-disable-account-creation', $html );
		}
	}

	public function testSpecialPageAfterQueryFormSubmission() {
		$this->setUserLang( 'qqx' );
		ConvertibleTimestamp::setFakeTime( '20240200040506' );
		$testPerformer = $this->getUserForSuccess();

		// Create a few testing global blocks, so that we can check the table shows the correct information
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$firstGlobalBlockStatus = $globalBlockManager->block(
			'1.2.3.4', 'testing IP block', '1 week', $testPerformer
		);
		$this->assertStatusGood( $firstGlobalBlockStatus );
		$secondGlobalBlockStatus = $globalBlockManager->block(
			'4.5.6.7/23', 'testing range block', '2 weeks', $testPerformer
		);
		$this->assertStatusGood( $secondGlobalBlockStatus );
		$secondGlobalBlockId = $secondGlobalBlockStatus->getValue()['id'];
		$thirdGlobalBlockStatus = $globalBlockManager->block(
			'1.2.3.4/20', 'testing', '3 weeks', $testPerformer, [ 'anon-only' ]
		);
		$this->assertStatusGood( $thirdGlobalBlockStatus );
		$globallyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();
		$fourthGlobalBlockStatus = $globalBlockManager->block(
			$globallyBlockedUser, 'testing', 'infinite', $testPerformer, [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $fourthGlobalBlockStatus );
		$notGloballyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();

		// Set-up the valid request and get a test user which has the necessary rights.
		$wpTargetsValue = "Non-existent-test-user-1\n#12345\n$notGloballyBlockedUser\n" .
			"$globallyBlockedUser\n1.2.3.4\n1.2.3.4/20\n#$secondGlobalBlockId";
		$fauxRequest = new FauxRequest( [ 'wpTargets' => $wpTargetsValue, 'wpMethod' => 'search' ], true );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $testPerformer );

		$queryFormHtml = $this->verifyQueryFormPresent( $html );
		// Verify that the wpTargets field is populated with the value of wpTargets
		$this->assertStringContainsString( $wpTargetsValue, $queryFormHtml );
		// Check that the block form is shown (at this point just the table and not any form elements are present).
		$this->assertStringContainsString( '(globalblocking-mass-block-legend', $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		$massGlobalBlockTableHtml = $this->verifyMassGlobalBlockTableShown( $html );
		// Verify that the valid targets have rows in the table.
		$expectedTargetsShown = [
			[ $notGloballyBlockedUser, $notGloballyBlockedUser, null ],
			[ $globallyBlockedUser, $globallyBlockedUser, 'infinity' ],
			[ '1.2.3.4', '1.2.3.4', '20240207040506' ],
			[ '1.2.3.4/20', '1.2.3.4/20', '20240221040506' ],
			[ '#2', "(globalblocking-global-block-id: $secondGlobalBlockId", '20240214040506' ],
		];
		foreach ( $expectedTargetsShown as $expectedTargetData ) {
			$expectedTargetName = $expectedTargetData[0];
			$expectedTargetNameForDisplay = $expectedTargetData[1];
			$expectedExpiry = $expectedTargetData[2];
			// Find the row for this target
			$rowHtml = $this->getAndExpectSingleMatchingElement(
				$massGlobalBlockTableHtml, "[data-mw-globalblocking-target=\"$expectedTargetName\"]"
			);
			// Check that the row has the target name present, along with the checkbox to select the user for
			// global blocking / unblocking.
			$this->assertStringContainsString( $expectedTargetNameForDisplay, $rowHtml );
			$this->assertStringContainsString( "wpActionTarget[]", $rowHtml );
			if ( $expectedExpiry === null ) {
				$this->assertStringContainsString( '(globalblocking-mass-block-not-blocked', $rowHtml );
			} else {
				$this->assertStringContainsString( '(globalblocking-mass-block-blocked', $rowHtml );
				$this->assertStringContainsString(
					RequestContext::getMain()->getLanguage()->formatExpiry( $expectedExpiry ), $rowHtml
				);
			}
		}
		// Verify that the invalid targets have a row in the table, which indicates the reason for the target not
		// being valid.
		$rowHtml = $this->getAndExpectSingleMatchingElement(
			$html, "[data-mw-globalblocking-target=\"Non-existent-test-user-1\"]"
		);
		$this->assertStringContainsString(
			'(globalblocking-block-target-invalid: Non-existent-test-user-1', $rowHtml
		);
		$rowHtml = $this->getAndExpectSingleMatchingElement(
			$html, "[data-mw-globalblocking-target=\"#12345\"]"
		);
		$this->assertStringContainsString( '(globalblocking-notblocked-id: #12345', $rowHtml );
	}

	private function assertGlobalBlocksTableEmpty() {
		$this->newSelectQueryBuilder()
			->select( '1' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertEmptyResult();
	}

	private function getFauxRequestForMassBlockSubmission( array $data, bool $performerHasSysopGroup = false ): array {
		// Set-up the request with a mismatching CSRF token.
		$testPerformer = $this->getUserForSuccess( $performerHasSysopGroup ? [ 'sysop' ] : [] );
		RequestContext::getMain()->setUser( $testPerformer );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		$fauxRequest = new FauxRequest(
			array_merge(
				[
					'wpEditToken' => RequestContext::getMain()->getCsrfTokenSet()->getToken(),
					'wpMethod' => 'block', 'wpExpiry' => '1 day', 'wpReason' => 'other',
					'wpReason-other' => 'Test reason',
				],
				$data
			),
			true, RequestContext::getMain()->getRequest()->getSession()
		);
		RequestContext::getMain()->setRequest( $fauxRequest );
		return [ $fauxRequest, $testPerformer ];
	}

	public function testSubmitWithMismatchingCsrfToken() {
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpEditToken' => 'abc', 'wpActionTarget' => '1.2.3.4', 'wpAction' => 'block',
			'wpTargets' => "1.2.3.4\n5.6.7.8",
		] );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$queryFormHtml = $this->verifyQueryFormPresent( $html );
		// Verify that the wpTargets field is populated with the value of wpTargets
		$this->assertStringContainsString( "1.2.3.4\n5.6.7.8", $queryFormHtml );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form does not submit and displays an error about the target
		$this->assertStringContainsString( '(globalblocking-mass-block-token-mismatch', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-success-block', $html );
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testSubmitWithNoActionTargets() {
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => '', 'wpAction' => 'block', 'wpTargets' => "1.2.3.4\n5.6.7.9",
		] );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$queryFormHtml = $this->verifyQueryFormPresent( $html );
		// Verify that the wpTargets field is populated with the value of wpTargets
		$this->assertStringContainsString( "1.2.3.4\n5.6.7.9", $queryFormHtml );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the page does not indicate any blocks were made.
		$this->assertStringNotContainsString( '(globalblocking-mass-success-block', $html );
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testSubmitForBlockOfNonExistentUser() {
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => 'Non-existent-test-user-1', 'wpAction' => 'block', 'wpAlsoLocal' => 1,
		] );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form does not submit and displays an error about the target
		$this->assertStringContainsString(
			'(globalblocking-mass-block-failure-block: Non-existent-test-user-1', $html
		);
		$this->assertStringContainsString(
			'(globalblocking-mass-block-failure-local: Non-existent-test-user-1', $html
		);
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testSubmitForBlockOfTooManyTargets() {
		$this->overrideConfigValue( 'GlobalBlockingMassGlobalBlockMaxTargets', 2 );
		// Execute the special page.
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => [ '1.2.3.4', '1.2.3.5', '1.2.3.6' ], 'wpAction' => 'block',
		] );
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form errors out to due to too many targets being unblocked at once
		$this->assertStringContainsString( '(globalblocking-mass-block-too-many-targets-to-block', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-block-success-block', $html );
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testSubmitForBlockOfMultipleTargets() {
		ConvertibleTimestamp::setFakeTime( '20240506070809' );
		// Creation of retroactive autoblocks would cause issues with assertions later in this test, so skip them.
		$this->overrideConfigValue( 'GlobalBlockingMaximumIPsToRetroactivelyAutoblock', 0 );
		$testTargetUsername = $this->getMutableTestUser()->getUserIdentity()->getName();
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => [ $testTargetUsername, '1.2.3.4', '1.2.4.0/24' ], 'wpAction' => 'block',
			'wpAnonOnly' => 1, 'wpAutoBlock' => 1, 'wpExpiry' => '1 day', 'wpReason-other' => 'test',
			'wpReason' => 'Testing', 'wpBlockEmail' => 1,
		] );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form successfully globally blocked, but did not locally block.
		$this->assertStringContainsString( '(globalblocking-mass-block-success-block', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-block-success-local', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-block-failure-block', $html );
		$this->newSelectQueryBuilder()
			->select( [
				'gb_id', 'gb_address', 'gb_enable_autoblock', 'gb_create_account',
				'gb_anon_only', 'gb_block_email', 'gb_expiry', 'gb_reason'
			] )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertResultSet( [
				[ '1', $testTargetUsername, '1', '0', '0', '1', '20240507070809', 'Testing: test' ],
				[ '2', '1.2.3.4', '0', '0', '1', '1', '20240507070809', 'Testing: test' ],
				[ '3', '1.2.4.0/24', '0', '0', '1', '1', '20240507070809', 'Testing: test' ],
			] );
	}

	public function testSubmitForBlockWithAlsoLocalWhenUserMissingSysopRights() {
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => '1.2.3.4', 'wpAction' => 'block', 'wpAlsoLocal' => 1,
		] );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form does not submit successfully
		$this->assertStringContainsString( '(globalblocking-mass-block-success-block: 1.2.3.4', $html );
		$this->assertStringContainsString( '(globalblocking-mass-block-failure-local: 1.2.3.4', $html );
	}

	public function testSubmitForBlockOfExistingBlockId() {
		ConvertibleTimestamp::setFakeTime( '20240506070809' );
		// Creation of retroactive autoblocks would cause issues with assertions later in this test, so skip them.
		$this->overrideConfigValue( 'GlobalBlockingMaximumIPsToRetroactivelyAutoblock', 0 );
		// Create a global block on a test user
		$globallyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$globalBlockStatus = $globalBlockManager->block(
			$globallyBlockedUser, 'existing block', 'infinite', $this->getUserForSuccess()
		);
		$this->assertStatusGood( $globalBlockStatus );
		$globalBlockId = $globalBlockStatus->getValue()['id'];
		$this->newSelectQueryBuilder()
			->select( [
				'gb_id', 'gb_address', 'gb_enable_autoblock', 'gb_create_account',
				'gb_anon_only', 'gb_expiry', 'gb_reason',
			] )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertRowValue( [
				(string)$globalBlockId, $globallyBlockedUser, '0', '1', '0', 'infinity', 'existing block',
			] );
		// Execute the special page.
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => [ '#' . $globalBlockId ], 'wpAction' => 'block',
			'wpAnonOnly' => 1, 'wpAutoBlock' => 1, 'wpExpiry' => '1 day', 'wpReason-other' => 'test',
			'wpReason' => 'other', 'wpMarkBot' => 1, 'wpAlsoLocal' => 1,
		], true );
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, true );
		// Verify that the form successfully globally blocked and locally blocked.
		$this->assertStringContainsString( '(globalblocking-mass-block-success-block', $html );
		$this->assertStringContainsString( '(globalblocking-mass-block-success-local', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-block-failure-block', $html );
		// Verify the parameters of the created global block
		$this->newSelectQueryBuilder()
			->select( [
				'gb_id', 'gb_address', 'gb_enable_autoblock', 'gb_create_account',
				'gb_anon_only', 'gb_expiry', 'gb_reason',
			] )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertRowValue( [
				(string)$globalBlockId, $globallyBlockedUser, '1', '0', '0', '20240507070809', 'test',
			] );
		// Verify the parameters of the created local block
		$localBlock = $this->getServiceContainer()->getDatabaseBlockStore()->newFromTarget( $globallyBlockedUser );
		$this->assertNotNull( $localBlock );
		$this->assertTrue( $performer->equals( $localBlock->getBlocker() ) );
		$this->assertTrue( $localBlock->isAutoblocking() );
		$this->assertTrue( $localBlock->isHardblock() );
		$this->assertFalse( $localBlock->appliesToUsertalk() );
		$this->assertFalse( $localBlock->isEmailBlocked() );
		$this->assertFalse( $localBlock->isCreateAccountBlocked() );
		$this->assertFalse( $localBlock->isEmailBlocked() );
		// Check that the local block was marked as a bot action
		$this->newSelectQueryBuilder()
			->select( 'rc_bot' )
			->from( 'recentchanges' )
			->join( 'logging', null, 'rc_logid = log_id' )
			->where( [ 'log_type' => 'block' ] )
			->caller( __METHOD__ )
			->assertFieldValue( 1 );
	}

	public function testSubmitForBlockWhenModifyingAutoblock() {
		// Get a global autoblock ID for use in the tests
		$globallyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$globalBlockStatus = $globalBlockManager->block(
			$globallyBlockedUser, 'existing block', 'infinite', $this->getUserForSuccess(),
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalBlockStatus );
		$globalBlockId = $globalBlockStatus->getValue()['id'];
		$globalAutoblockStatus = $globalBlockManager->autoblock( $globalBlockId, '1.2.3.4' );
		$this->assertStatusGood( $globalAutoblockStatus );
		$globalAutoblockId = $globalAutoblockStatus->getValue()['id'];

		// Execute the special page.
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => '#' . $globalAutoblockId, 'wpAction' => 'block',
		] );
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form does not submit successfully
		$this->assertStringContainsString(
			"(globalblocking-mass-block-failure-block: #$globalAutoblockId", $html
		);
		$this->assertStringNotContainsString( '(globalblocking-mass-block-failure-local', $html );
	}

	public function testSubmitForUnblockOfNotBlockedIPs() {
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => [ '1.2.3.4', '1.2.2.4/20' ], 'wpAction' => 'unblock',
		] );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form does not submit and displays an error
		$this->assertStringContainsString(
			'(globalblocking-mass-block-failure-unblock: 1.2.3.4(comma-separator)1.2.2.4/20', $html
		);
	}

	public function testSubmitForUnblockOfTooManyTargets() {
		$this->overrideConfigValue( 'GlobalBlockingMassGlobalBlockMaxTargets', 2 );
		// Block an IP so that we can check that it was not unblocked due to the form submission failure.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$globalIPBlockStatus = $globalBlockManager->block(
			'1.2.3.5', 'ip block', '1 day', $this->getUserForSuccess()
		);
		$this->assertStatusGood( $globalIPBlockStatus );
		// Execute the special page.
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => [ '1.2.3.4', '1.2.3.5', '1.2.3.6' ], 'wpAction' => 'unblock',
		] );
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form errors out to due to too many targets being unblocked at once
		$this->assertStringContainsString( '(globalblocking-mass-block-too-many-targets-to-unblock', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-block-success-unblock', $html );
		// Check that the global block on the IP has not been removed, as the form failed to submit.
		$this->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValue( 1 );
	}

	public function testSubmitForUnblockOfMultipleTargets() {
		ConvertibleTimestamp::setFakeTime( '20240506070809' );
		// Get a block on a user, an autoblock, and an IP block to be able to test removing using the form.
		$globallyBlockedUser = $this->getMutableTestUser()->getUserIdentity()->getName();
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$globalBlockStatus = $globalBlockManager->block(
			$globallyBlockedUser, 'existing block', 'infinite', $this->getUserForSuccess(),
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalBlockStatus );
		$globalBlockId = $globalBlockStatus->getValue()['id'];
		$globalAutoblockStatus = $globalBlockManager->autoblock( $globalBlockId, '1.2.3.4' );
		$this->assertStatusGood( $globalAutoblockStatus );
		$globalAutoblockId = $globalAutoblockStatus->getValue()['id'];
		$globalIPBlockStatus = $globalBlockManager->block(
			'1.2.3.5', 'ip block', '1 day', $this->getUserForSuccess()
		);
		$this->assertStatusGood( $globalIPBlockStatus );
		$this->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValue( 3 );

		// Execute the special page.
		[ $fauxRequest, $performer ] = $this->getFauxRequestForMassBlockSubmission( [
			'wpActionTarget' => [ '#' . $globalAutoblockId, $globallyBlockedUser, '1.2.3.5' ], 'wpAction' => 'unblock',
		] );
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $performer );
		$this->verifyQueryFormPresent( $html );
		$this->verifyMassGlobalBlockTableShown( $html );
		$this->verifyMassGlobalBlockFormFieldsShown( $html, false );
		// Verify that the form successfully submits
		$this->assertStringContainsString( '(globalblocking-mass-block-success-unblock', $html );
		$this->assertStringNotContainsString( '(globalblocking-mass-block-failure-unblock', $html );
		$this->assertGlobalBlocksTableEmpty();
	}
}
