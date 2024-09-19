<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Extension\GlobalBlocking\CentralAuthHooks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\CentralAuthHooks
 * @group Database
 */
class CentralAuthHooksTest extends MediaWikiIntegrationTestCase {

	use MockAuthorityTrait;

	private static UserIdentity $testGloballyBlockedUser;
	private static UserIdentity $testUnblockedUser;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
		// These tests only work if CentralAuth is loaded
		$this->markTestSkippedIfExtensionNotLoaded( 'CentralAuth' );
	}

	private function getCentralAuthHooks(): CentralAuthHooks {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		return new CentralAuthHooks(
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$this->getServiceContainer()->getLinkRenderer()
		);
	}

	public function testOnCentralAuthInfoFieldsForUserWithoutPermissionsAndNotGloballyBlockedTarget() {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setAuthority( $this->mockAnonNullAuthority() );
		// Run the hook
		$attribs = [];
		$this->getCentralAuthHooks()->onCentralAuthInfoFields(
			CentralAuthUser::getInstance( self::$testUnblockedUser ), $context, $attribs
		);
		// Verify nothing was added if the the target is not globally blocked and there are no action links.
		$this->assertArrayEquals( [], $attribs );
	}

	/** @dataProvider provideOnCentralAuthInfoFields */
	public function testOnCentralAuthInfoFields( callable $target, bool $shouldBeBlocked ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		// Ensure that tool links will be always shown
		$context->setAuthority( $this->mockRegisteredUltimateAuthority() );
		// The title needs to be set as the GlobalBlockingLinkBuilder will use it to generate the action links
		$context->setTitle( Title::makeTitle( NS_SPECIAL, 'CentralAuth' ) );
		$this->setUserLang( 'qqx' );
		// Run the hook
		$attribs = [];
		$this->getCentralAuthHooks()->onCentralAuthInfoFields(
			CentralAuthUser::getInstance( $target() ), $context, $attribs
		);
		// Verify the structure of the global blocking bullet point.
		$this->assertArrayHasKey( 'globalblock', $attribs );
		$this->assertSame( 'globalblocking-centralauth-admin-info-globalblock', $attribs['globalblock']['label'] );
		if ( $shouldBeBlocked ) {
			$this->assertStringContainsString( 'centralauth-admin-yes', $attribs['globalblock']['data'] );
			$this->assertStringContainsString( 'globalblocking-list-whitelist', $attribs['globalblock']['data'] );
		} else {
			$this->assertStringContainsString( 'centralauth-admin-no', $attribs['globalblock']['data'] );
			$this->assertStringContainsString( 'globalblocking-list-block', $attribs['globalblock']['data'] );
		}
	}

	public static function provideOnCentralAuthInfoFields() {
		return [
			'Special:CentralAuth for unblocked user' => [ fn () => self::$testUnblockedUser, false ],
			'Special:CentralAuth for globally blocked user' => [ fn () => self::$testGloballyBlockedUser, true ],
		];
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
		// Get a testing global block on an IP address
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$testGloballyBlockedUser = $this->getMutableTestUser()->getUserIdentity();
		$testUnblockedUser = $this->getTestUser()->getUserIdentity();
		$this->assertStatusGood( $globalBlockManager->block(
			$testGloballyBlockedUser, 'Test reason3', '3 days', $this->getTestUser( [ 'steward' ] )->getUser()
		) );
		self::$testGloballyBlockedUser = $testGloballyBlockedUser;
		self::$testUnblockedUser = $testUnblockedUser;
	}
}
