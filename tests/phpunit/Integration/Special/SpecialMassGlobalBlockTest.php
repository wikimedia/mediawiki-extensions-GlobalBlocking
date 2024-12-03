<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\User;
use PermissionsError;
use ReadOnlyError;
use SpecialPageTestBase;
use UserBlockedError;
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
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
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
			->placeBlock( true );
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
		return DOMCompat::getInnerHTML( $element );
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
		$queryResultTableHtml = $this->assertAndGetByElementId( $html, 'mw-globalblocking-mass-block-table' );
		// Verify that the table headings are all present
		$this->assertStringContainsString( '(globalblocking-list-table-heading-target', $queryResultTableHtml );
		$this->assertStringContainsString( '(globalblocking-mass-block-header-status', $queryResultTableHtml );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-expiry', $queryResultTableHtml );
		$this->assertStringContainsString( '(globalblocking-list-table-heading-params', $queryResultTableHtml );
		// Verify that the valid targets have rows in the table.
		$expectedTargetsShown = [
			[ $notGloballyBlockedUser, null ],
			[ $globallyBlockedUser, 'infinity' ],
			[ '1.2.3.4', '20240207040506' ],
			[ '1.2.3.4/20', '20240221040506' ],
			[ "(globalblocking-global-block-id: $secondGlobalBlockId", '20240214040506' ],
		];
		foreach ( $expectedTargetsShown as $expectedTargetData ) {
			$expectedTargetName = $expectedTargetData[0];
			$expectedExpiry = $expectedTargetData[1];
			$this->assertStringContainsString( $expectedTargetName, $queryResultTableHtml );
			if ( $expectedExpiry === null ) {
				$this->assertStringContainsString( '(globalblocking-mass-block-not-blocked', $queryResultTableHtml );
			} else {
				$this->assertStringContainsString( '(globalblocking-mass-block-blocked', $queryResultTableHtml );
				$this->assertStringContainsString(
					RequestContext::getMain()->getLanguage()->formatExpiry( $expectedExpiry ), $queryResultTableHtml
				);
			}
		}
		// Verify that the invalid targets have a row in the table, which indicates the reason for the target not
		// being valid.
		$this->assertStringContainsString(
			'(globalblocking-block-target-invalid: Non-existent-test-user-1', $queryResultTableHtml
		);
		$this->assertStringContainsString( '(globalblocking-notblocked-id: #12345', $queryResultTableHtml );
	}
}
