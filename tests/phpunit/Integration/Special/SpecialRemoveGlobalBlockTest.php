<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\SpecialPage\FormSpecialPageTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialRemoveGlobalBlock
 * @group Database
 */
class SpecialRemoveGlobalBlockTest extends FormSpecialPageTestCase {
	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'RemoveGlobalBlock' );
	}

	private function getUserForSuccess() {
		return $this->getTestUser( [ 'steward' ] )->getUser();
	}

	public function testViewPageBeforeSubmission() {
		// Need to get the full HTML to be able to check that the subtitle links are present
		[ $html ] = $this->executeSpecialPage(
			'', new FauxRequest(), null, $this->getUserForSuccess(), true
		);
		// Check that the form fields exist
		$this->assertStringContainsString( '(globalblocking-target', $html );
		$this->assertStringContainsString( '(globalblocking-unblock-reason', $html );
		$this->assertStringContainsString( '(globalblocking-unblock-submit', $html );
		// Verify that the form title and description are present
		$this->assertStringContainsString( '(globalblocking-unblock-legend', $html );
		$this->assertStringContainsString( '(globalblocking-unblock-intro', $html );
		// Verify that the subtitle links are present
		$this->assertStringContainsString( '(globalblocklist', $html );
		// Verify the special page title is correctly set
		$this->assertStringContainsString( '(globalblocking-unblock', $html );
	}

	public function testSubmitFormWithIPThatWasNotBlocked() {
		// Set-up the valid request and get a test user which has the necessary rights.
		$testPerformer = $this->getUserForSuccess();
		RequestContext::getMain()->setUser( $testPerformer );
		$fauxRequest = new FauxRequest(
			[ 'wpReason' => 'testing', 'wpEditToken' => $testPerformer->getEditToken() ],
			true,
			RequestContext::getMain()->getRequest()->getSession()
		);
		// Assign the fake valid request to the main request context, as well as updating the session user
		// so that the CSRF token is a valid token for the request user.
		RequestContext::getMain()->setRequest( $fauxRequest );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '1.2.3.4', $fauxRequest, null, $this->getUserForSuccess() );
		$this->assertStringContainsString( '(globalblocking-notblocked', $html );
	}

	/** @dataProvider provideValidTargets */
	public function testSuccessfulSubmissionOfForm( $target ) {
		$testPerformer = $this->getUserForSuccess();
		RequestContext::getMain()->setUser( $testPerformer );
		// Block an IP which we will later unblock as part of the test.
		GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager()
			->block( $target, 'Test block', '1 day', $this->getUserForSuccess() );
		// Set-up the valid request.
		$fauxRequest = new FauxRequest(
			[ 'target' => $target, 'wpReason' => 'testing', 'wpEditToken' => $testPerformer->getEditToken() ],
			true,
			RequestContext::getMain()->getRequest()->getSession()
		);
		// Assign the fake valid request to the main request context, as well as updating the session user
		// so that the CSRF token is a valid token for the request user.
		RequestContext::getMain()->setRequest( $fauxRequest );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $this->getUserForSuccess() );
		// Verify that the 'globalblocking-unblock-unblocked' success message is present.
		$this->assertStringContainsString( "(globalblocking-unblock-unblocked: $target", $html );
		// Verify that the 'globalblocking-return' link is present.
		$this->assertStringContainsString( '(globalblocking-return', $html );
	}

	public static function provideValidTargets() {
		return [
			'IP address' => [ '1.2.3.5' ],
			'IP range' => [ '1.2.3.0/24' ],
		];
	}

	public function testSuccessfulSubmissionOfFormForAccountTarget() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
		$this->testSuccessfulSubmissionOfForm( $this->getMutableTestUser()->getUser()->getName() );
	}
}
