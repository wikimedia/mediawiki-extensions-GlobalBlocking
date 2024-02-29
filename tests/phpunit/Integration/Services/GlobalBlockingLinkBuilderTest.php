<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder
 * @group Database
 */
class GlobalBlockingLinkBuilderTest extends MediaWikiIntegrationTestCase {

	use MockAuthorityTrait;

	/** @dataProvider provideMaybeLinkUserpage */
	public function testMaybeLinkUserpage( $wikiID, $user, $expectedReturnValue ) {
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$this->assertSame(
			$expectedReturnValue,
			$globalBlockingLinkBuilder->maybeLinkUserpage( $wikiID, $user ),
			'Unexpected return value from ::maybeLinkUserpage'
		);
	}

	public static function provideMaybeLinkUserpage() {
		return [
			'Unknown wiki ID' => [
				// The $wikiID argument for ::maybeLinkUserpage
				'abcdefgxyz',
				// The $user argument for ::maybeLinkUserpage
				'User',
				// The expected return value from ::maybeLinkUserpage
				'User',
			],
		];
	}

	private function getMockSpecialPage( $specialPageName, $mockAuthority ): SpecialPage {
		// Create a mock SpecialPage object which mocks ::getName but returns a real
		// LinkRenderer object from ::getLinkRenderer.
		$mockSpecialPage = $this->createMock( SpecialPage::class );
		$mockSpecialPage->method( 'getName' )
			->willReturn( $specialPageName );
		$mockSpecialPage->method( 'getLinkRenderer' )
			->willReturn( $this->getServiceContainer()->getLinkRenderer() );
		$mockSpecialPage->method( 'getAuthority' )
			->willReturn( $mockAuthority );
		$mockSpecialPage->method( 'getLanguage' )
			->willReturn( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' ) );
		$mockSpecialPage->method( 'msg' )
			->willReturnCallback( static function ( $key ) {
				return wfMessage( $key )->inLanguage( 'qqx' );
			} );
		return $mockSpecialPage;
	}

	public function testBuildSubtitleLinksWithNullAuthorityOnSpecialGlobalBlockList() {
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();

		$this->assertSame(
			'',
			$globalBlockingLinkBuilder->buildSubtitleLinks(
				$this->getMockSpecialPage( 'GlobalBlockList', $this->mockRegisteredNullAuthority() )
			),
			'::buildSubtitleLinks should be empty when the authority is null and the title is Special:GlobalBlockList'
		);
	}

	public function testBuildSubtitleLinksWithGlobalBlockWhitelistRight() {
		// Call the method under test
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$specialPage = $this->getMockSpecialPage(
			'RemoveGlobalBlock',
			$this->mockRegisteredAuthorityWithPermissions( [ 'globalblock-whitelist' ] )
		);
		$actualSubtitleLinks = $globalBlockingLinkBuilder->buildSubtitleLinks( $specialPage );
		// Perform assertions to verify that the method under test provided the expected subtitle links.
		$this->assertStringContainsString(
			'GlobalBlockList',
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to return a link to Special:GlobalBlockList'
		);
		$this->assertStringContainsString(
			SpecialPage::getTitleFor( 'GlobalBlockStatus' )->getText(),
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to return a link to Special:GlobalBlockStatus'
		);
		$this->assertStringNotContainsString(
			SpecialPage::getTitleFor( 'RemoveGlobalBlock' )->getText(),
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to not return a link to Special:RemoveGlobalBlock'
		);
	}

	public function testBuildSubtitleLinksWithGlobalBlockAndGlobalBlockWhitelistRights() {
		// Call the method under test
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$specialPage = $this->getMockSpecialPage(
			'GlobalBlockStatus',
			$this->mockRegisteredAuthorityWithPermissions( [ 'globalblock-whitelist', 'globalblock' ] )
		);
		$actualSubtitleLinks = $globalBlockingLinkBuilder->buildSubtitleLinks( $specialPage );
		// Perform assertions to verify that the method under test provided the expected subtitle links.
		$this->assertStringContainsString(
			'GlobalBlockList',
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to return a link to Special:GlobalBlockList'
		);
		$this->assertStringNotContainsString(
			SpecialPage::getTitleFor( 'GlobalBlockStatus' )->getText(),
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to not return a link to Special:GlobalBlockStatus'
		);
		$this->assertStringContainsString(
			SpecialPage::getTitleFor( 'RemoveGlobalBlock' )->getText(),
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to return a link to Special:RemoveGlobalBlock'
		);
	}

	public function testBuildSubtitleLinksForUserWithGlobalBlockAndEditInterfaceRights() {
		// Call the method under test
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$specialPage = $this->getMockSpecialPage(
			'GlobalBlock',
			$this->mockRegisteredAuthorityWithPermissions( [ 'editinterface', 'globalblock' ] )
		);
		$actualSubtitleLinks = $globalBlockingLinkBuilder->buildSubtitleLinks( $specialPage );
		// Perform assertions to verify that the method under test provided the expected subtitle links.
		$this->assertStringContainsString(
			'(globalblocking-block-edit-dropdown)',
			$actualSubtitleLinks,
			'Expected ::buildSubtitleLinks to return a link to Special:GlobalBlock to modify the dropdown options'
		);
	}
}
