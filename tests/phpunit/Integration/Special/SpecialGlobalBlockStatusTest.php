<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use ErrorPageError;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
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
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
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
				$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', true );
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

	public function testDisabledIfApplyGlobalBlocksIsFalse() {
		$this->setMwGlobals( 'wgApplyGlobalBlocks', false );
		$this->expectException( ErrorPageError::class );
		$this->expectExceptionMessage( wfMessage( 'globalblocking-whitelist-notapplied' )->text() );
		RequestContext::getMain()->setUser( $this->getTestSysop()->getUser() );
		$this->newSpecialPage()->execute( '' );
	}

	public function testLocallyDisableBlockForInvalidUsername() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', true );
		$performer = $this->getTestSysop()->getUser();

		// Simulate the user using Special:GlobalBlockStatus to locally enable the block
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
			'globalblocking-notblocked-new', $html,
			'The incorrect error message for the form was used.'
		);
	}
}
