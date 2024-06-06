<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\UserFactory;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks
 * @group Database
 */
class GlobalBlockingHooksTest extends MediaWikiIntegrationTestCase {
	private function getGlobalBlockingHooks(): GlobalBlockingHooks {
		return new GlobalBlockingHooks(
			$this->getServiceContainer()->getPermissionManager(),
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->getCommentFormatter(),
			$this->getServiceContainer()->getCentralIdLookup(),
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockingLinkBuilder(),
		);
	}

	/** @dataProvider provideOnSpecialContributionsBeforeMainOutput */
	public function testOnSpecialContributionsBeforeMainOutput(
		$username, $shouldDisplayBlockBanner, $expectedBlockTarget
	) {
		$this->setUserLang( 'qqx' );
		$specialPage = new SpecialPage();
		$specialPage->setContext( RequestContext::getMain() );
		// Call the method under test
		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $username, UserFactory::RIGOR_NONE );
		$this->getGlobalBlockingHooks()->onSpecialContributionsBeforeMainOutput( $user->getId(), $user, $specialPage );
		// Assert that the HTML output in the OutputPage instance is as expected
		if ( $shouldDisplayBlockBanner ) {
			$this->assertStringContainsString(
				'(globalblocking-contribs-notice',
				$specialPage->getOutput()->getHTML(),
				'Expected block banner to be displayed for IP on Special:Contributions'
			);
			$this->assertStringContainsString(
				$expectedBlockTarget,
				$specialPage->getOutput()->getHTML(),
				'The block displayed on Special:Contributions was not the expected block.'
			);
		} else {
			$this->assertStringNotContainsString(
				'(globalblocking-contribs-notice',
				$specialPage->getOutput()->getHTML(),
				'The IP is not blocked, so no block banner should be displayed on Special:Contributions'
			);
		}
	}

	public static function provideOnSpecialContributionsBeforeMainOutput() {
		return [
			'Special:Contributions for 1.2.3.4' => [ '1.2.3.4', true, '1.2.3.4' ],
			'Special:Contributions for 1.2.3.5'	=> [ '1.2.3.5', true, '1.2.3.0/24' ],
			'Special:Contributions for 127.0.0.2' => [ '127.0.0.2', false, null ],
		];
	}

	public function addDBDataOnce() {
		// Create two test GlobalBlocks on an IP and IP range in the database for use in the above tests. These
		// should not be modified by any code in GlobalBlockingHooks, so this can be added once per-class.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUser();
		$globalBlockManager->block( '1.2.3.4', 'Test reason', 'infinity', $testPerformer );
		$globalBlockManager->block( '1.2.3.4/24', 'Test reason2', '1 month', $testPerformer );
	}
}
