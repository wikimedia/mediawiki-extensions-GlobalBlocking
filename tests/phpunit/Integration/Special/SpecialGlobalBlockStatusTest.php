<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use ErrorPageError;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\SpecialPage\FormSpecialPageTestCase;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use PermissionsError;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockStatus
 * @group Database
 */
class SpecialGlobalBlockStatusTest extends FormSpecialPageTestCase {

	use MockAuthorityTrait;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'GlobalBlockStatus' );
	}

	public function testUserDoesNotHaveRequiredRight() {
		$this->expectException( PermissionsError::class );
		// The "normal" test user should not have the globalblock-whitelist right
		RequestContext::getMain()->setUser( $this->getTestUser()->getUser() );
		$this->newSpecialPage()->execute( '127.0.0.1/24' );
	}

	/** @dataProvider provideGlobalBlockTargetTypes */
	public function testLocallyDisableBlock( $targetType ) {
		// Generate the target based on the provided target type.
		switch ( $targetType ) {
			case 'ip':
				$target = '1.2.3.4';
				break;
			case 'user':
				$target = $this->getTestUser()->getUser()->getName();
				break;
			default:
				$this->fail( 'Unrecognised target type' );
		}
		// Perform a block on an IP to be able to locally disable.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$blockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( $target, 'test', 'infinite', $this->getTestUser( [ 'steward' ] )->getUser() );
		$this->assertStatusGood( $blockStatus );
		$globalBlockId = $blockStatus->getValue()['id'];

		// Simulate the user using Special:GlobalBlockStatus to locally disable the block
		$performer = $this->getTestSysop()->getUser();
		$request = new FauxRequest(
			[
				'address' => $target,
				'wpReason' => '',
				'wpWhitelistStatus' => 1,
				'wpEditToken' => $performer->getEditToken(),
			],
			true
		);

		[ $html ] = $this->executeSpecialPage( $target, $request, 'qqx', $performer );

		$this->assertNotFalse(
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()->getLocalWhitelistInfo( $globalBlockId ),
			'Block should be locally disabled after using the special page'
		);

		$this->assertStringContainsString( 'globalblocking-return', $html, 'The success message was not present' );
	}

	public static function provideGlobalBlockTargetTypes() {
		return [
			'Single IP address' => [ 'ip' ],
			'Username' => [ 'user' ],
		];
	}

	public function testLocallyEnableBlock() {
		// Perform a block on an IP to be able to locally disable.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$blockStatus = $globalBlockingServices->getGlobalBlockManager()
			->block( '1.2.3.4', 'test', 'infinite', $this->getTestUser( [ 'steward' ] )->getUser() );
		$this->assertStatusGood( $blockStatus );
		$globalBlockId = $blockStatus->getValue()['id'];

		$performer = $this->getTestSysop()->getUser();

		// Locally disable the block so we are able to test locally enabling it
		$globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '1.2.3.4', 'test', $performer );
		$this->assertNotFalse(
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()->getLocalWhitelistInfo( $globalBlockId ),
			'Block should be locally disabled for the test'
		);

		// Simulate the user using Special:GlobalBlockStatus to locally enable the block
		$request = new FauxRequest(
			[
				// wpWhitelistStatus not being present in the fake request data means the checkbox was unchecked.
				'address' => '1.2.3.4',
				'wpReason' => 'removing local disable',
				'wpEditToken' => $performer->getEditToken(),
			],
			true
		);

		[ $html ] = $this->executeSpecialPage(
			'',
			$request,
			'qqx',
			$performer
		);

		$this->assertFalse(
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()->getLocalWhitelistInfo( $globalBlockId ),
			'Block should be locally enabled after using the special page'
		);
		$this->assertStringContainsString( 'globalblocking-return', $html, 'The success message was not present' );
	}

	public function testLocallyEnableGlobalAutoblock() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		// Get a testing global autoblock which we can locally disable, and then use the special page to re-enable.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockManager = $globalBlockingServices->getGlobalBlockManager();
		$globalAccountBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test1234', 'infinite',
			$this->getTestUser( [ 'steward' ] )->getUser(), [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalAccountBlockStatus );
		$accountGlobalBlockId = $globalAccountBlockStatus->getValue()['id'];
		$autoBlockStatus = $globalBlockManager->autoblock( $accountGlobalBlockId, '7.8.9.0' );
		$this->assertStatusGood( $autoBlockStatus );
		$autoBlockId = $autoBlockStatus->getValue()['id'];
		// Locally disable the autoblock
		$performer = $this->getTestSysop()->getUser();
		$globalBlockingServices->getGlobalBlockLocalStatusManager()
			->locallyDisableBlock( '#' . $autoBlockId, 'test', $performer );

		// Use the special page to locally re-enable the global autoblock.
		$request = new FauxRequest(
			[
				// wpWhitelistStatus not being present in the fake request data means the checkbox was unchecked.
				'address' => '#' . $autoBlockId, 'wpReason' => 'removing local disable',
				'wpEditToken' => $performer->getEditToken(),
			],
			true
		);
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );
		// Check that the correct success message is present
		$this->assertStringContainsString( '(globalblocking-whitelist-dewhitelisted-target-is-id', $html );
		$this->assertFalse(
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()->getLocalStatusInfo( $autoBlockId ),
			'Block should be locally enabled after using the special page'
		);
	}

	public function testLocallyDisableGlobalAutoblock() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		// Get a testing global autoblock which we can use the special page to locally disable
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockManager = $globalBlockingServices->getGlobalBlockManager();
		$globalAccountBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test1234', 'infinite',
			$this->getTestUser( [ 'steward' ] )->getUser(), [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalAccountBlockStatus );
		$accountGlobalBlockId = $globalAccountBlockStatus->getValue()['id'];
		$autoBlockStatus = $globalBlockManager->autoblock( $accountGlobalBlockId, '7.8.9.0' );
		$this->assertStatusGood( $autoBlockStatus );
		$autoBlockId = $autoBlockStatus->getValue()['id'];
		// Use the special page to locally disable the global autoblock.
		$performer = $this->getTestSysop()->getUser();
		$request = new FauxRequest(
			[
				'address' => '#' . $autoBlockId, 'wpReason' => 'local disable', 'wpWhitelistStatus' => 1,
				'wpEditToken' => $performer->getEditToken(),
			],
			true
		);
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );
		// Check that the correct success message is present
		$this->assertStringContainsString( '(globalblocking-whitelist-whitelisted-target-is-id', $html );
		$this->assertArrayEquals(
			[ 'user' => $performer->getId(), 'reason' => 'local disable' ],
			$globalBlockingServices->getGlobalBlockLocalStatusLookup()->getLocalStatusInfo( $autoBlockId )
		);
	}

	public function testDisabledIfApplyGlobalBlocksIsFalse() {
		$this->overrideConfigValue( 'ApplyGlobalBlocks', false );
		$this->expectException( ErrorPageError::class );
		$this->expectExceptionMessage( wfMessage( 'globalblocking-whitelist-notapplied' )->text() );
		RequestContext::getMain()->setUser( $this->getTestSysop()->getUser() );
		$this->newSpecialPage()->execute( '' );
	}

	public function testLocallyDisableBlockForInvalidUsername() {
		$performer = $this->getTestSysop()->getUser();

		// Simulate the user using Special:GlobalBlockStatus to locally disable the block
		$request = new FauxRequest(
			[
				// The username # is invalid.
				'address' => '#',
				'wpReason' => 'local disable',
				'wpWhitelistStatus' => 1,
				'wpEditToken' => $performer->getEditToken(),
			],
			true
		);

		[ $html ] = $this->executeSpecialPage(
			'',
			$request,
			'qqx',
			$performer
		);

		$this->assertStringContainsString(
			'htmlform-user-not-valid', $html,
			'The incorrect error message for the form was used.'
		);
	}

	public function testLocallyDisableBlockForNotBlockedUser() {
		$performer = $this->getTestSysop()->getUser();

		// Simulate the user using Special:GlobalBlockStatus to attempt to locally disable a block which does not exist
		$request = new FauxRequest(
			[
				'address' => '#1234',
				'wpReason' => 'local disable',
				'wpWhitelistStatus' => 1,
				'wpEditToken' => $performer->getEditToken(),
			],
			true
		);

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString(
			'globalblocking-notblocked-id', $html,
			'The incorrect error message for the form was used.'
		);
	}
}
