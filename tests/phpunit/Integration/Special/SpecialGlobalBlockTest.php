<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Tests\SpecialPage\FormSpecialPageTestCase;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlock
 * @group Database
 */
class SpecialGlobalBlockTest extends FormSpecialPageTestCase {

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'GlobalBlock' );
	}

	/** @dataProvider provideSetParameter */
	public function testSetParameter( $providedTarget, $fromSubpage, $expectedTarget ) {
		if ( !$fromSubpage ) {
			$mockRequest = new FauxRequest( [ 'wpAddress' => $providedTarget ], true );
			RequestContext::getMain()->setRequest( $mockRequest );
		}
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( $fromSubpage ? $providedTarget : '' );
		$this->assertSame( $expectedTarget, $specialGlobalBlock->target );
	}

	public static function provideSetParameter() {
		return [
			'Empty target from request' => [ '', false, '' ],
			'Empty target from subpage' => [ '', true, '' ],
			'IP target from request' => [ '127.0.0.1', false, '127.0.0.1' ],
			'IP target from subpage' => [ '127.0.0.1', true, '127.0.0.1' ],
			'IP range from subpage' => [ '1.2.3.4/24', true, '1.2.3.0/24' ],
			'User from request' => [ 'testing_test', false, 'Testing test' ],
		];
	}

	/** @dataProvider provideLoadExistingBlockWithExistingBlock */
	public function testLoadExistingBlockWithExistingBlock( $target, $existingBlockOptions, $expectedReturnArray ) {
		// Perform a block on $target so that we can test the loadExistingBlock method returning
		// data on an existing block.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$existingBlockStatus = $globalBlockManager->block(
			$target, 'test', 'infinite', $this->getTestUser( [ 'steward' ] )->getUser(),
			$existingBlockOptions
		);
		$this->assertStatusGood( $existingBlockStatus );
		// Set the target to the user which was blocked.
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( $target );
		// Call the loadExistingBlock method
		$this->assertArrayEquals(
			$expectedReturnArray,
			$specialGlobalBlock->loadExistingBlock(),
			false,
			true
		);
	}

	public static function provideLoadExistingBlockWithExistingBlock() {
		return [
			'Existing block disables account creation, anon-only' => [
				'127.0.0.1',
				[ 'anon-only' ],
				[
					'anononly' => 1, 'createAccount' => 1, 'enableAutoblock' => 0,
					'reason' => 'test', 'expiry' => 'indefinite',
				],
			],
			'Existing block allows account creation' => [
				'127.0.0.1',
				[ 'allow-account-creation' ],
				[
					'anononly' => 0, 'createAccount' => 0, 'enableAutoblock' => 0,
					'reason' => 'test', 'expiry' => 'indefinite',
				],
			],
			'Existing block with no options provided' => [
				'127.0.0.1',
				[],
				[
					'anononly' => 0, 'createAccount' => 1, 'enableAutoblock' => 0,
					'reason' => 'test', 'expiry' => 'indefinite',
				],
			],
		];
	}

	public function testLoadExistingBlockWithUserBlockThatEnablesAutoblocking() {
		$this->testLoadExistingBlockWithExistingBlock(
			$this->getTestUser()->getUserIdentity()->getName(),
			[ 'enable-autoblock' ],
			[
				'anononly' => 0, 'createAccount' => 1, 'enableAutoblock' => 1,
				'reason' => 'test', 'expiry' => 'indefinite',
			]
		);
	}

	public function testLoadExistingBlockWithUserBlockThatDoesNotEnableAutoblocking() {
		$this->testLoadExistingBlockWithExistingBlock(
			$this->getTestUser()->getUserIdentity()->getName(),
			[],
			[
				'anononly' => 0, 'createAccount' => 1, 'enableAutoblock' => 0,
				'reason' => 'test', 'expiry' => 'indefinite',
			]
		);
	}

	/** @dataProvider provideTargetsWhichAreNotBlocked */
	public function testLoadExistingBlockWithNoBlock( $username ) {
		// Set the target to 127.0.0.1, which is not blocked.
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( $username );
		// Call the loadExistingBlock method
		$this->assertArrayEquals( [], $specialGlobalBlock->loadExistingBlock() );
	}

	public static function provideTargetsWhichAreNotBlocked() {
		return [
			'IP address' => [ '127.0.0.1' ],
			'Non-existent user' => [ 'Non-existent test user1234' ],
		];
	}

	public function testLoadExistingBlockForIPThatIsGloballyAutoblocked() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		// Perform a block on a test user and then perform a global autoblock on an IP using the global user block
		// as the parent block.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$globalAccountBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test1234', 'infinite',
			$this->getTestUser( [ 'steward' ] )->getUser(), [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalAccountBlockStatus );
		$accountGlobalBlockId = $globalAccountBlockStatus->getValue()['id'];
		$autoBlockStatus = $globalBlockManager->autoblock( $accountGlobalBlockId, '7.8.9.0' );
		// Set the target as the IP that is globally autoblocked.
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( '7.8.9.0' );
		// Call the loadExistingBlock method, and expect that nothing is returned.
		$this->assertArrayEquals( [], $specialGlobalBlock->loadExistingBlock() );
	}

	private function getUserForSuccess( array $additionalGroups = [] ) {
		return $this->getMutableTestUser( array_merge( [ 'steward' ], $additionalGroups ) )->getUser();
	}

	public function testViewSpecialPageWithoutSysop() {
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest(), null, $this->getUserForSuccess() );
		// Verify that the form fields are present.
		$this->assertStringContainsString( '(globalblocking-target', $html );
		$this->assertStringContainsString( '(globalblocking-block-expiry', $html );
		$this->assertStringContainsString( '(globalblocking-block-reason', $html );
		$this->assertStringContainsString( '(globalblocking-ipbanononly', $html );
		$this->assertStringContainsString( '(globalblocking-block-submit', $html );
		// The also local checkboxes should not be present unless the user has the 'block' right on the local wiki
		$this->assertStringNotContainsString( '(globalblocking-also-local', $html );
		// Verify that the description is present
		$this->assertStringContainsString( '(globalblocking-block-intro', $html );
		// Verify the form title is present
		$this->assertStringContainsString( '(globalblocking-block-legend', $html );
	}

	public function testViewSpecialPageWithSysop() {
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest(), null, $this->getUserForSuccess( [ 'sysop' ] ) );
		// Verify that the form fields are present.
		$this->assertStringContainsString( '(globalblocking-target', $html );
		$this->assertStringContainsString( '(globalblocking-block-expiry', $html );
		$this->assertStringContainsString( '(globalblocking-block-reason', $html );
		$this->assertStringContainsString( '(globalblocking-ipbanononly', $html );
		$this->assertStringContainsString( '(globalblocking-block-submit', $html );
		// The also local checkboxes should be present as the user has 'block' right on the local wiki
		$this->assertStringContainsString( '(globalblocking-also-local', $html );
		$this->assertStringContainsString( '(globalblocking-also-local-talk', $html );
		$this->assertStringContainsString( '(globalblocking-also-local-email', $html );
		$this->assertStringContainsString( '(globalblocking-also-local-soft', $html );
		// Verify that the description is present
		$this->assertStringContainsString( '(globalblocking-block-intro', $html );
		// Verify the form title is present
		$this->assertStringContainsString( '(globalblocking-block-legend', $html );
	}

	public function testViewSpecialPageForAlreadyGloballyBlockedIP() {
		// Perform a block on an IP
		GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager()->block(
			'1.2.3.4', 'global-block-test-reason', 'infinite', $this->getUserForSuccess()
		);
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '1.2.3.4', new FauxRequest(), null, $this->getUserForSuccess() );
		// Verify that the already blocked banner is shown
		$this->assertStringContainsString( '(globalblocking-block-alreadyblocked', $html );
		// Verify that the extract of the global blocking logs is shown
		$this->assertStringContainsString( 'gblblock/gblock', $html );
		// Verify that the reason field is pre-filled with the reason used for the existing block
		$this->assertStringContainsString( 'global-block-test-reason', $html );
	}

	public function testSubmitWithNonExistentUser() {
		// Set-up the valid request and get a test user which has the necessary rights.
		$testPerformer = $this->getUserForSuccess();
		RequestContext::getMain()->setUser( $testPerformer );
		$fauxRequest = new FauxRequest(
			[
				// Test with a single target user, with both notices being added.
				'wpAddress' => 'Non-existent-test-user-1', 'wpExpiry' => '1 day',
				'wpReason' => 'other', 'wpReason-other' => 'Test reason',
				'wpEditToken' => $testPerformer->getEditToken(),
			],
			true,
			RequestContext::getMain()->getRequest()->getSession()
		);
		// Assign the fake valid request to the main request context, as well as updating the session user
		// so that the CSRF token is a valid token for the request user.
		RequestContext::getMain()->setRequest( $fauxRequest );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '', $fauxRequest, null, $this->getUserForSuccess() );
		// Verify that the form does not submit and displays an error about the target
		$this->assertStringContainsString( '(globalblocking-block-target-invalid', $html );
		$this->assertStringNotContainsString( '(globalblocking-block-success', $html );
		// Double check that no block was performed.
		$this->assertSame(
			0,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLookup()
				->getGlobalBlockId( 'Non-existent-test-user-1' ),
			'No block should have been performed as the target of the block did not exist.'
		);
	}

	public function testSubmitForIPTarget() {
		// Set-up the valid request and get a test user which has the necessary rights.
		$testPerformer = $this->getUserForSuccess();
		RequestContext::getMain()->setUser( $testPerformer );
		$fauxRequest = new FauxRequest(
			[
				// Test with a single target user
				'wpAddress' => '1.2.3.4', 'wpExpiry' => '1 day',
				'wpReason' => 'other', 'wpReason-other' => 'Test reason',
				'wpAnonOnly' => 1, 'wpCreateAccount' => 1,
				'wpEditToken' => $testPerformer->getEditToken(),
			],
			true,
			RequestContext::getMain()->getRequest()->getSession()
		);
		// Assign the fake valid request to the main request context, as well as updating the session user
		// so that the CSRF token is a valid token for the request user.
		RequestContext::getMain()->setRequest( $fauxRequest );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '1.2.3.4', $fauxRequest, null, $this->getUserForSuccess() );
		// Verify that the form does submits successfully and displays the messages added by ::onSuccess
		$this->assertStringContainsString( '(globalblocking-block-success', $html );
		$this->assertStringContainsString( '(globalblocking-add-block', $html );
		// Check that no local block is performed, as it was not asked for.
		$this->assertNull(
			$this->getServiceContainer()->getDatabaseBlockStore()->newFromTarget( '1.2.3.4' ),
			'No local block should have been performed as it was not specified.'
		);
		// Double check that the block was actually performed
		$globalBlockLookup = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLookup();
		$this->assertSame(
			1,
			$globalBlockLookup->getGlobalBlockId( '1.2.3.4' ),
			'A block on 1.2.3.4 should have been applied as the form was successfully submitted.'
		);
		// Verify that the global block settings are as expected.
		$actualGlobalBlock = $globalBlockLookup->getGlobalBlockingBlock( '1.2.3.4', 0 );
		$this->assertTrue( (bool)$actualGlobalBlock->gb_create_account );
		$this->assertTrue( (bool)$actualGlobalBlock->gb_anon_only );
		$this->assertFalse( (bool)$actualGlobalBlock->gb_enable_autoblock );
	}

	public function testSubmitForIPTargetWhenModifyingBlock() {
		ConvertibleTimestamp::setFakeTime( '20210405060708' );
		$testPerformer = $this->getUserForSuccess();
		RequestContext::getMain()->setUser( $testPerformer );
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		// Perform a block on the IP target, so that we can modify it using the special page.
		$globalBlockManager = $globalBlockingServices->getGlobalBlockManager();
		$globalBlockManager->block( '1.2.3.4', 'test block to modify', '1 hour', $testPerformer );
		// Set-up the valid request to modify the block
		$fauxRequest = new FauxRequest(
			[
				// Test with a single target user
				'wpAddress' => '1.2.3.4', 'wpExpiry' => '1 day',
				'wpReason' => 'other', 'wpReason-other' => 'Test reason',
				'wpAnonOnly' => 0, 'wpCreateAccount' => 0, 'wpModify' => 1,
				'wpPrevious' => '1.2.3.4', 'wpEditToken' => $testPerformer->getEditToken(),
			],
			true,
			RequestContext::getMain()->getRequest()->getSession()
		);
		// Assign the fake valid request to the main request context, as well as updating the session user
		// so that the CSRF token is a valid token for the request user.
		RequestContext::getMain()->setRequest( $fauxRequest );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		RequestContext::getMain()->setTitle( SpecialPage::getTitleFor( 'GlobalBlock' ) );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '1.2.3.4', $fauxRequest, null, $this->getUserForSuccess() );
		// Verify that the form does submits successfully and displays the messages added by ::onSuccess
		$this->assertStringContainsString( '(globalblocking-modify-success', $html );
		$this->assertStringContainsString( '(globalblocking-add-block', $html );
		// Double check that the global block was actually updated.
		$globalBlockLookup = $globalBlockingServices->getGlobalBlockLookup();
		$this->assertSame( 1, $globalBlockLookup->getGlobalBlockId( '1.2.3.4' ) );
		// Verify that the global block settings are as expected.
		$actualGlobalBlock = $globalBlockLookup->getGlobalBlockingBlock( '1.2.3.4', 0 );
		$this->assertFalse( (bool)$actualGlobalBlock->gb_create_account );
		$this->assertFalse( (bool)$actualGlobalBlock->gb_anon_only );
		$this->assertFalse( (bool)$actualGlobalBlock->gb_enable_autoblock );
		$this->assertSame( '20210406060708', $this->getDb()->decodeExpiry( $actualGlobalBlock->gb_expiry ) );
	}

	public function testSubmitForUserTargetWhenLocalBlockSpecified() {
		// Set-up the valid request and get a test user which has the necessary rights.
		$testPerformer = $this->getUserForSuccess();
		RequestContext::getMain()->setUser( $testPerformer );
		$testTarget = $this->getTestUser()->getUser();
		$fauxRequest = new FauxRequest(
			[
				// Test with a single target user, with both notices being added.
				'wpAddress' => $testTarget->getName(), 'wpExpiry' => 'indefinite',
				'wpReason' => 'other', 'wpReason-other' => 'Test reason for account block',
				'wpAlsoLocal' => 1, 'wpAutoBlock' => 1, 'wpEditToken' => $testPerformer->getEditToken(),
			],
			true,
			RequestContext::getMain()->getRequest()->getSession()
		);
		// Assign the fake valid request to the main request context, as well as updating the session user
		// so that the CSRF token is a valid token for the request user.
		RequestContext::getMain()->setRequest( $fauxRequest );
		RequestContext::getMain()->getRequest()->getSession()->setUser( $testPerformer );
		// Execute the special page.
		[ $html ] = $this->executeSpecialPage( '1.2.3.4', $fauxRequest, null, $this->getUserForSuccess( [ 'sysop' ] ) );
		// Verify that the form does submits successfully and displays the messages added by ::onSuccess
		$this->assertStringContainsString( '(globalblocking-block-success', $html );
		$this->assertStringContainsString( '(globalblocking-add-block', $html );
		// Double check that the global and local block was actually performed
		$globalBlockLookup = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLookup();
		$this->assertSame( 1, $globalBlockLookup->getGlobalBlockId( $testTarget->getName() ) );
		// Verify that the global block settings are as expected.
		$actualGlobalBlock = $globalBlockLookup->getGlobalBlockingBlock( null, $testTarget->getId() );
		$this->assertFalse( (bool)$actualGlobalBlock->gb_create_account );
		$this->assertFalse( (bool)$actualGlobalBlock->gb_anon_only );
		$this->assertTrue( (bool)$actualGlobalBlock->gb_enable_autoblock );
		// Verify that the local block settings are as expected.
		$actualLocalBlock = $this->getServiceContainer()->getDatabaseBlockStore()->newFromTarget( $testTarget );
		$this->assertNotNull( $actualLocalBlock );
		$this->assertFalse( $actualLocalBlock->isCreateAccountBlocked() );
	}
}
