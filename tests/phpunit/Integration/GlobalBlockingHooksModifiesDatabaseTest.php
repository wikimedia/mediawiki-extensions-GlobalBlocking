<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks
 * @group Database
 */
class GlobalBlockingHooksModifiesDatabaseTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	private function getGlobalBlockingHooks(): GlobalBlockingHooks {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		return new GlobalBlockingHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->getCommentFormatter(),
			$this->getServiceContainer()->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$this->getServiceContainer()->getUserNameUtils(),
			$globalBlockingServices->getGlobalBlockingUserVisibilityLookup(),
			$globalBlockingServices->getGlobalBlockManager(),
			$globalBlockingServices->getGlobalBlockDetailsRenderer(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder()
		);
	}

	public function testOnSpreadAnyEditBlockForNoSuchBlock() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		$blockWasSpread = false;
		$this->getGlobalBlockingHooks()->onSpreadAnyEditBlock( $this->getTestUser()->getUser(), $blockWasSpread );
		$this->assertFalse( $blockWasSpread );
	}

	public function testOnSpreadAnyEditBlockWhenAutoblockShouldBeCreated() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		RequestContext::getMain()->getRequest()->setIP( '1.2.3.4' );
		// Create a global block on a target which causes autoblocks
		$testGloballyBlockedUser = $this->getMutableTestUser()->getUser();
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$parentBlockStatus = $globalBlockManager->block(
			$testGloballyBlockedUser->getName(), 'Test reason3', '3 days',
			$this->getTestUser( 'steward' )->getUser(), [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $parentBlockStatus );
		$parentBlockId = $parentBlockStatus->getValue()['id'];
		// Call the SpreadAnyEditBlock to cause an autoblock on the request IP
		$blockWasSpread = false;
		$this->getGlobalBlockingHooks()->onSpreadAnyEditBlock( $testGloballyBlockedUser, $blockWasSpread );
		$this->assertTrue( $blockWasSpread );
		// Check that the autoblock exists on the request IP
		$this->newSelectQueryBuilder()
			->select( '1' )
			->from( 'globalblocks' )
			->where( [ 'gb_address' => '1.2.3.4', 'gb_autoblock_parent_id' => $parentBlockId ] )
			->caller( __METHOD__ )
			->assertFieldValue( 1 );
	}
}
