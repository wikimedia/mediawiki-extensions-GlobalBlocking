<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MainConfigNames;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder
 * @group Database
 */
class GlobalBlockingLinkBuilderTest extends MediaWikiIntegrationTestCase {

	use MockAuthorityTrait;

	private static UserIdentity $testGloballyBlockedUser;
	private static UserIdentity $testUnblockedUser;
	private static int $testGloballyBlockedUserGlobalBlockId;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( 'CentralIdLookupProvider', 'local' );
		// Add the current site to the SiteStore so that we can get a URL for the site.
		$sitesTable = $this->getServiceContainer()->getSiteStore();
		$site = new MediaWikiSite();
		$site->setGlobalId( 'enwiki' );
		// We need to set a page path, otherwise this is not considered a valid site. Use enwiki's path as a mock value.
		$site->setPath( MediaWikiSite::PATH_PAGE, "https://en.wikipedia.org/wiki/$1" );
		$site->setPath( MediaWikiSite::PATH_FILE, "https://en.wikipedia.org/w/$1" );
		$sitesTable->saveSite( $site );
	}

	/** @dataProvider provideMaybeLinkUserpage */
	public function testMaybeLinkUserpage( $wikiID, $user, $expectedReturnValue ) {
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$this->assertSame(
			$expectedReturnValue,
			$globalBlockingLinkBuilder->maybeLinkUserpage(
				$wikiID, $user, SpecialPage::getTitleFor( 'GlobalBlockList' )
			),
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

	public function testMaybeLinkUserpageForValidWiki() {
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$actualLink = $globalBlockingLinkBuilder->maybeLinkUserpage(
			'enwiki', 'TestUser', SpecialPage::getTitleFor( 'GlobalBlockList' )
		);
		$this->assertStringContainsString( "https://en.wikipedia.org/wiki/User:TestUser", $actualLink );
	}

	private function getMockSpecialPage( $specialPageName, $mockAuthority ): SpecialPage {
		// Create a mock SpecialPage object which mocks ::getName but returns a real
		// LinkRenderer object from ::getLinkRenderer.
		$mockSpecialPage = $this->createMock( SpecialPage::class );
		$mockSpecialPage->method( 'getName' )
			->willReturn( $specialPageName );
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

	public function testGetActionLinksWithNoPermissions() {
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$this->assertSame(
			'',
			$globalBlockingLinkBuilder->getActionLinks(
				$this->mockRegisteredNullAuthority(),
				'Testing',
				$this->newContext()
			),
			'::getActionLinks should return an empty string when the authority has no permissions'
		);
	}

	/** @dataProvider provideGetActionLinks */
	public function testGetActionLinksForSysop(
		callable $targetCallback, $shouldCheckBlockStatus, $shouldDisplayLocalDisableActionLink
	) {
		// Set the language as qqx so that we can look for message keys in the HTML output we are testing
		$this->setUserLang( 'qqx' );
		// Call the method under test
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$actualActionLinks = $globalBlockingLinkBuilder->getActionLinks(
			$this->mockRegisteredAuthorityWithPermissions( [ 'globalblock-whitelist' ] ),
			$targetCallback(),
			RequestContext::getMain(),
			$shouldCheckBlockStatus
		);
		// Perform assertions to verify that the expected action links are present in the returned HTML
		if ( $shouldDisplayLocalDisableActionLink ) {
			$this->assertStringContainsString(
				'(globalblocking-list-whitelist)',
				$actualActionLinks,
				'Expected ::getActionLinks to return a link to Special:GlobalBlockStatus'
			);
		} else {
			$this->assertStringNotContainsString(
				'(globalblocking-list-whitelist)',
				$actualActionLinks,
				'Expected ::getActionLinks to not have a link to Special:GlobalBlockStatus'
			);
		}
		$this->assertStringNotContainsString(
			'(globalblocking-list-modify)',
			$actualActionLinks,
			'Expected ::getActionLinks to not return a link to Special:GlobalBlock, because the user does not ' .
			'have the authority to modify a global block.'
		);
		$this->assertStringNotContainsString(
			'(globalblocking-list-unblock)',
			$actualActionLinks,
			'Expected ::getActionLinks to not return a link to Special:RemoveGlobalBlock, because the user ' .
			'does not have the authority to remove a global block.'
		);
	}

	public static function provideGetActionLinks() {
		return [
			'User is not blocked' => [ fn () => self::$testUnblockedUser->getName(), true, false ],
			'User is not blocked, but set to not check block status' => [
				fn () => self::$testUnblockedUser->getName(), false, true,
			],
			'User is blocked' => [ fn () => self::$testGloballyBlockedUser->getName(), true, true ],
			'User is blocked, target is global block ID' => [
				fn () => '#' . self::$testGloballyBlockedUserGlobalBlockId, true, true,
			]
		];
	}

	/** @dataProvider provideGetActionLinks */
	public function testGetActionLinksForSteward(
		callable $targetCallback, $shouldCheckBlockStatus, $shouldDisplayActionLinksForBlockedUser
	) {
		// Set the language as qqx so that we can look for message keys in the HTML output we are testing
		$this->setUserLang( 'qqx' );
		// We need to set the title for the provided $context, as it is used to generate external links
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setTitle( Title::makeTitle( NS_SPECIAL, 'GlobalBlockList' ) );
		// Call the method under test
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$actualActionLinks = $globalBlockingLinkBuilder->getActionLinks(
			$this->mockRegisteredAuthorityWithPermissions( [ 'globalblock' ] ),
			$targetCallback(),
			$context,
			$shouldCheckBlockStatus
		);
		// Perform assertions to verify that the expected action links are present in the returned HTML
		$this->assertStringNotContainsString(
			'(globalblocking-list-whitelist)',
			$actualActionLinks,
			'Expected ::getActionLinks to not return a link to Special:GlobalBlockStatus as the authority ' .
			'not have the right to use that special page.'
		);
		if ( $shouldDisplayActionLinksForBlockedUser ) {
			if ( GlobalBlockLookup::isAGlobalBlockId( $targetCallback() ) ) {
				$this->assertStringNotContainsString(
					'(globalblocking-list-modify)',
					$actualActionLinks,
					'Expected ::getActionLinks to not have a modify block link'
				);
			} else {
				$this->assertStringContainsString(
					'(globalblocking-list-modify)',
					$actualActionLinks,
					'Expected ::getActionLinks to return a modify block link'
				);
			}
			$this->assertStringContainsString(
				'(globalblocking-list-unblock)',
				$actualActionLinks,
				'Expected ::getActionLinks to return a link to Special:RemoveGlobalBlock'
			);
			$this->assertStringNotContainsString(
				'(globalblocking-list-block)',
				$actualActionLinks,
				'Expected ::getActionLinks to not have a block link'
			);
		} else {
			$this->assertStringNotContainsString(
				'(globalblocking-list-modify)',
				$actualActionLinks,
				'Expected ::getActionLinks to not have a modify block link'
			);
			$this->assertStringNotContainsString(
				'(globalblocking-list-unblock)',
				$actualActionLinks,
				'Expected ::getActionLinks to have no remove block link'
			);
			if ( GlobalBlockLookup::isAGlobalBlockId( $targetCallback() ) ) {
				$this->assertStringNotContainsString(
					'(globalblocking-list-block)',
					$actualActionLinks,
					'Expected ::getActionLinks to not have a block link'
				);
			} else {
				$this->assertStringContainsString(
					'(globalblocking-list-block)',
					$actualActionLinks,
					'Expected ::getActionLinks to have a block link'
				);
			}
		}
	}

	/** @dataProvider provideCentralWikiConfigValuesForLocalUrl */
	public function testGetLinkToCentralWikiSpecialPageForLocalLink( $globalBlockingCentralWikiConfigValue ) {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', $globalBlockingCentralWikiConfigValue );
		$this->setUserLang( 'qqx' );
		// Call the method under test
		$globalBlockingLinkBuilder = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingLinkBuilder();
		$actualLinkHtml = $globalBlockingLinkBuilder->getLinkToCentralWikiSpecialPage(
			'Log', 'test1234', $this->createMock( LinkTarget::class ),
			[ 'page' => '1.2.3.4' ]
		);
		// Verify that the link HTML is as expected
		$this->assertStringContainsString(
			'test1234', $actualLinkHtml, 'The link text is missing'
		);
		$this->assertStringContainsString(
			'page=1.2.3.4', $actualLinkHtml,
			'The logs link should be filtered to just the target user.'
		);
	}

	public static function provideCentralWikiConfigValuesForLocalUrl() {
		return [
			'Invalid central wiki name' => [ 'undefined-wiki' ],
			'Central wiki name is false' => [ false ],
		];
	}

	/** @dataProvider provideContentLanguages */
	public function testGetLinkToCentralWikiSpecialPageForExternalLink( $contentLanguage ) {
		$this->overrideConfigValues( [
			MainConfigNames::LanguageCode => $contentLanguage,
			'GlobalBlockingCentralWiki' => 'mediawiki'
		] );
		$this->setUserLang( 'qqx' );
		// Get a partially mocked GlobalBlockingLinkBuilder, which mocks ::getForeignURL to return a URL
		$objectUnderTest = $this->getMockBuilder( GlobalBlockingLinkBuilder::class )
			->setConstructorArgs( [
				new ServiceOptions(
					GlobalBlockingLinkBuilder::CONSTRUCTOR_OPTIONS,
					$this->getServiceContainer()->getMainConfig()
				),
				$this->getServiceContainer()->getLinkRenderer(),
				GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLookup()
			] )
			->onlyMethods( [ 'getForeignURL' ] )
			->getMock();
		$objectUnderTest->method( 'getForeignURL' )
			->with( 'mediawiki', 'Special:Log' )
			->willReturn( 'https://meta.wikimedia.org/wiki/Special:Log' );
		// Call the method under test
		$actualLinkHtml = $objectUnderTest->getLinkToCentralWikiSpecialPage(
			'Log', 'test1234', Title::makeTitle( NS_SPECIAL, 'Contributions/1.2.3.4' ),
			[ 'page' => '1.2.3.4' ]
		);
		// Verify that the link HTML is as expected, with the special name always in English (ignoring the content
		// language of the wiki) (T374277).
		$this->assertStringContainsString(
			'https://meta.wikimedia.org/wiki/Special:Log', $actualLinkHtml,
			'The link should go to the central wiki.'
		);
		$this->assertStringContainsString(
			'test1234', $actualLinkHtml, 'The link text is missing.'
		);
		$this->assertStringContainsString(
			'page=1.2.3.4', $actualLinkHtml,
			'The logs link should be filtered to just the target user.'
		);
	}

	public static function provideContentLanguages() {
		return [
			'Content language as English' => [ 'en' ],
			'Content language as Spanish' => [ 'es' ],
		];
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( 'CentralIdLookupProvider', 'local' );
		// Get a globally blocked user so that we can test with
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globallyBlockedTestUser = $this->getMutableTestUser()->getUserIdentity();
		$globalBlockStatus = $globalBlockingServices->getGlobalBlockManager()->block(
			$globallyBlockedTestUser, 'test', 'indefinite', $this->getTestUser( [ 'steward' ] )->getUser()
		);
		$this->assertStatusGood( $globalBlockStatus );
		self::$testGloballyBlockedUserGlobalBlockId = $globalBlockStatus->getValue()['id'];
		self::$testGloballyBlockedUser = $globallyBlockedTestUser;
		self::$testUnblockedUser = $this->getMutableTestUser()->getUserIdentity();
	}
}
