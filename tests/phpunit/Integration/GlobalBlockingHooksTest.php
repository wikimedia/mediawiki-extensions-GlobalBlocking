<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks
 * @group Database
 */
class GlobalBlockingHooksTest extends MediaWikiIntegrationTestCase {

	use MockAuthorityTrait;

	private static UserIdentity $testGloballyBlockedUser;
	private static UserIdentity $hiddenUser;
	private static UserIdentity $unblockedUser;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
	}

	private function getGlobalBlockingHooks(): GlobalBlockingHooks {
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		return new GlobalBlockingHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->getCommentFormatter(),
			$this->getServiceContainer()->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalBlockLocalStatusLookup(),
			$this->getServiceContainer()->getUserNameUtils(),
			$globalBlockingServices->getGlobalBlockingUserVisibilityLookup(),
			$this->getServiceContainer()->getUserIdentityLookup()
		);
	}

	/** @dataProvider provideOnSpecialContributionsBeforeMainOutput */
	public function testOnSpecialContributionsBeforeMainOutput(
		$username, $shouldDisplayBlockBanner, $expectedBlockTarget
	) {
		$this->setUserLang( 'qqx' );
		$specialPage = new SpecialPage();
		$specialPage->setContext( RequestContext::getMain() );
		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $username, UserFactory::RIGOR_NONE );
		// Call the method under test
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
				'The user is not blocked, so no block banner should be displayed on Special:Contributions'
			);
		}
	}

	public static function provideOnSpecialContributionsBeforeMainOutput() {
		return [
			'Special:Contributions for 1.2.3.4' => [ '1.2.3.4', true, '1.2.3.4' ],
			'Special:Contributions for 1.2.3.5'	=> [ '1.2.3.5', true, '1.2.3.0/24' ],
			'Special:Contributions for 127.0.0.2' => [ '127.0.0.2', false, null ],
			'Special:Contributions for non-existent user' => [ 'Non-existent-test-user-1234', false, null ],
			'Special:Contributions for invalid username' => [ ':', false, null ],
		];
	}

	public function testOnSpecialContributionsBeforeMainOutputForGloballyBlockedUser() {
		$this->testOnSpecialContributionsBeforeMainOutput(
			self::$testGloballyBlockedUser->getName(), true, self::$testGloballyBlockedUser->getName()
		);
	}

	public function testOnSpecialContributionsBeforeMainOutputForGloballyBlockedUserWhenGlobalAccountBlocksDisabled() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', false );
		$this->testOnSpecialContributionsBeforeMainOutput(
			self::$testGloballyBlockedUser->getName(), false, null
		);
	}

	public function testOnSpecialContributionsBeforeMainOutputForNotBlockedUser() {
		$this->testOnSpecialContributionsBeforeMainOutput(
			self::$unblockedUser, false, null
		);
	}

	/** @dataProvider provideUserHasSuppressRight */
	public function testOnSpecialContributionsBeforeMainOutputForHiddenUser( $userHasHideUser ) {
		if ( $userHasHideUser ) {
			$authority = $this->mockRegisteredUltimateAuthority();
		} else {
			$authority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'hideuser' ] );
		}
		RequestContext::getMain()->setAuthority( $authority );
		$this->testOnSpecialContributionsBeforeMainOutput(
			self::$hiddenUser->getName(), $userHasHideUser, self::$hiddenUser->getName()
		);
	}

	public static function provideUserHasSuppressRight() {
		return [
			'User has hideuser right' => [ true ],
			'User does not have hideuser right' => [ false ],
		];
	}

	public static function provideUserAndIPCombinations() {
		return [
			'User logged out using IP 1.2.3.4' => [ '1.2.3.4', '1.2.3.4', '1.2.3.4' ],
			'User logged out using IP 1.2.3.5'	=> [ '1.2.3.5', '1.2.3.5', '1.2.3.0/24' ],
			'User logged out using IP 127.0.0.2' => [ '127.0.0.2', '127.0.0.2', null ],
			'Non-existent user with no IP' => [ 'Non-existent-test-user-1234', null, null ],
			'Non-existent user with IP 127.0.0.2' => [ 'Non-existent-test-user-1234', '127.0.0.2', null ],
			'Invalid username with no IP' => [ ':', null, null ],
		];
	}

	/** @dataProvider provideUserAndIPCombinations */
	public function testOnUserIsBlockedGlobally( $username, $ip, $expectedBlockTarget ) {
		// Call the method under test.
		$blocked = false;
		$block = null;
		$hookReturnValue = $this->getGlobalBlockingHooks()->onUserIsBlockedGlobally(
			$this->getServiceContainer()->getUserFactory()->newFromName( $username, UserFactory::RIGOR_NONE ),
			$ip, $blocked, $block
		);
		// Verify that the return value and the $blocked boolean are as expected. They should be the opposite of
		// each other always, and $blocked should be true if $expectedBlockTarget is not null.
		$shouldBeBlocked = $expectedBlockTarget !== null;
		$this->assertSame( !$shouldBeBlocked, $hookReturnValue, 'The hook did not return the expected value' );
		$this->assertSame( $shouldBeBlocked, $blocked, 'The blocked status was not expected.' );
		if ( $shouldBeBlocked ) {
			$this->assertNotNull( $block, 'A block object should be defined.' );
			$this->assertSame(
				GlobalBlockingServices::wrap( $this->getServiceContainer() )
					->getGlobalBlockLookup()->getGlobalBlockId( $expectedBlockTarget ),
				$block->getId(),
				'The block returned by onUserIsBlockedGlobally was not the expected block.'
			);
		} else {
			$this->assertNull( $block, 'No block object should have been provided.' );
		}
	}

	public function testOnUserIsBlockedGloballyForGloballyBlockedUser() {
		$this->testOnUserIsBlockedGlobally(
			self::$testGloballyBlockedUser->getName(), '127.0.0.2', self::$testGloballyBlockedUser->getName()
		);
	}

	public function testOnUserIsBlockedGloballyForNotBlockedUserButBlockedViaIP() {
		$this->testOnUserIsBlockedGlobally( self::$unblockedUser->getName(), '1.2.3.6', '1.2.3.0/24' );
	}

	public function testOnUserIsBlockedGloballyForNotBlockedUser() {
		$this->testOnUserIsBlockedGlobally( self::$unblockedUser->getName(), null, null );
	}

	/** @dataProvider provideOnOtherBlockLogLink */
	public function testOnOtherBlockLogLink( $target, $shouldDisplayMessage ) {
		$this->setUserLang( 'qqx' );
		$msg = [];
		// Call the method under test
		$this->assertTrue( $this->getGlobalBlockingHooks()->onOtherBlockLogLink( $msg, $target ) );
		if ( $shouldDisplayMessage ) {
			$this->assertNotCount( 0, $msg, 'The message should be added if the user is blocked.' );
			$this->assertStringContainsString(
				'(globalblocking-loglink: ' . $target, $msg[0],
				'The block target was not as expected.'
			);
		} else {
			$this->assertCount( 0, $msg, 'The message should not be added if the user is not blocked.' );
		}
	}

	public static function provideOnOtherBlockLogLink() {
		return [
			'Target is 1.2.3.4' => [ '1.2.3.4', true ],
			'Target is 1.2.3.5'	=> [ '1.2.3.5', true ],
			'Target is 127.0.0.2' => [ '127.0.0.2', false ],
			'Target is a non-existent user' => [ 'Non-existent-test-user-1234', false ],
			'Target is an invalid username' => [ ':', false ],
		];
	}

	public function testOnOtherBlockLogLinkForGloballyBlockedUser() {
		$this->testOnOtherBlockLogLink( self::$testGloballyBlockedUser->getName(), true );
	}

	public function testOnOtherBlockLogLinkForGloballyBlockedUserWhenGlobalAccountBlocksAreDisabled() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', false );
		$this->testOnOtherBlockLogLink( self::$testGloballyBlockedUser->getName(), false );
	}

	public function testOnOtherBlockLogLinkForNotBlockedUser() {
		$this->testOnOtherBlockLogLink( self::$unblockedUser->getName(), false );
	}

	/** @dataProvider provideUserHasSuppressRight */
	public function testOnOtherBlockLogLinkForHiddenUser( $userHasHideUser ) {
		if ( $userHasHideUser ) {
			$authority = $this->mockRegisteredUltimateAuthority();
		} else {
			$authority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'hideuser' ] );
		}
		RequestContext::getMain()->setAuthority( $authority );
		$this->testOnOtherBlockLogLink( self::$hiddenUser->getName(), $userHasHideUser );
	}

	/** @dataProvider provideOnContributionsToolLinks */
	public function testOnContributionsToolLinks( $target, $userRights, $expectedLinkTexts ) {
		// Set the authority as a mock authority that has the provided user rights
		RequestContext::getMain()->setAuthority( $this->mockRegisteredAuthorityWithPermissions( $userRights ) );
		// Set the user language as qqx to compare to message keys
		$this->setUserLang( 'qqx' );
		// Call the method under test
		$tools = [];
		$specialPage = new SpecialPage();
		$specialPage->setContext( RequestContext::getMain() );
		$userObject = $this->getServiceContainer()->getUserFactory()->newFromName( $target, UserFactory::RIGOR_NONE );
		$targetId = $userObject !== null ? $userObject->getId() : 0;
		$this->getGlobalBlockingHooks()->onContributionsToolLinks(
			$targetId, Title::newFromText( $target, NS_USER ), $tools, $specialPage
		);
		if ( count( $expectedLinkTexts ) ) {
			// Verify that the expected links are present in the $tools array
			foreach ( $expectedLinkTexts as $expectedLinkText ) {
				$foundMatchingLink = false;
				foreach ( $tools as $tool ) {
					if ( strpos( $tool, $expectedLinkText ) !== false ) {
						$foundMatchingLink = true;
					}
				}
				$this->assertTrue( $foundMatchingLink, 'An expected link text was not found in the tools array.' );
			}
		} else {
			$this->assertCount( 0, $tools, 'No links should be added if the user does not have the required rights.' );
		}
	}

	public static function provideOnContributionsToolLinks() {
		return [
			'Target is 1.2.3.4 and user has globalblock right' => [
				'1.2.3.4', [ 'globalblock' ], [ '(globalblocking-contribs-modify', '(globalblocking-contribs-remove' ],
			],
			'Target is 1.2.3.4/24 and user has globalblock right' => [
				'1.2.3.4/24', [ 'globalblock' ],
				[ '(globalblocking-contribs-modify', '(globalblocking-contribs-remove' ],
			],
			'Target is 1.2.3.5 and user has globalblock right' => [
				'1.2.3.5', [ 'globalblock' ], [ '(globalblocking-contribs-block' ],
			],
			'Target is 127.0.0.2 and user has globalblock right' => [
				'127.0.0.2', [ 'globalblock' ], [ '(globalblocking-contribs-block' ],
			],
			'Target is 1.2.3.4 and user does not have globalblock right' => [ '1.2.3.4', [], [] ],
		];
	}

	public function testOnContributionsToolLinksForTooLongUsername() {
		// Make an example username invalid by setting the maximum length for usernames to 10 characters.
		// This should cover all situations where the username is invalid.
		$this->overrideConfigValue( MainConfigNames::MaxNameChars, '10' );
		$this->testOnContributionsToolLinks( 'TooLongUsername', [ 'globalblock' ], [] );
	}

	public function testOnContributionsToolLinksForGloballyBlockedUser() {
		$this->testOnContributionsToolLinks(
			self::$testGloballyBlockedUser->getName(), [ 'globalblock' ],
			[ '(globalblocking-contribs-modify', '(globalblocking-contribs-remove' ]
		);
	}

	public function testOnContributionsToolLinksForGloballyBlockedUserWhenGlobalAccountBlocksDisabled() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', false );
		$this->testOnContributionsToolLinks( self::$testGloballyBlockedUser->getName(), [ 'globalblock' ], [] );
	}

	public function testOnContributionsToolLinksForNotBlockedUser() {
		$this->testOnContributionsToolLinks(
			self::$unblockedUser->getName(), [ 'globalblock' ], [ '(globalblocking-contribs-block' ]
		);
	}

	/** @dataProvider provideUserHasSuppressRight */
	public function testOnContributionsToolLinksForHiddenUser( $userHasHideUser ) {
		if ( $userHasHideUser ) {
			$userRights = [ 'hideuser', 'globalblock' ];
			$expectedLinkTexts = [ '(globalblocking-contribs-modify', '(globalblocking-contribs-remove' ];
		} else {
			$userRights = [ 'globalblock' ];
			$expectedLinkTexts = [];
		}
		$this->testOnContributionsToolLinks( self::$hiddenUser->getName(), $userRights, $expectedLinkTexts );
	}

	public function testOnGetUserBlockWhenNoMatchingBlockFound() {
		$block = null;
		// Call the hook handler
		$this->assertTrue(
			$this->getGlobalBlockingHooks()->onGetUserBlock(
				$this->getServiceContainer()->getUserFactory()->newFromUserIdentity( static::$unblockedUser ),
				'127.0.0.2', $block
			),
			'::onGetUserBlock should always return true.'
		);
		// Assert that the $block argument is still null.
		$this->assertNull( $block, 'The $block argument should not be modified if no matching block is found.' );
	}

	public function testOnGetUserBlockWhenMatchingBlockFound() {
		$block = null;
		// Call the hook handler
		$this->assertTrue(
			$this->getGlobalBlockingHooks()->onGetUserBlock(
				$this->getServiceContainer()->getUserFactory()->newFromUserIdentity( static::$unblockedUser ),
				'1.2.3.4', $block
			),
			'::onGetUserBlock should always return true.'
		);
		// The $block argument should be a GlobalBlock instance as only a GlobalBlock applies to the given user.
		$this->assertInstanceOf( GlobalBlock::class, $block, 'Expected the $block to be a GlobalBlock instance' );
	}

	public function testOnGetUserBlockForCompositeBlock() {
		$block = new SystemBlock();
		// Call the hook handler
		$this->assertTrue(
			$this->getGlobalBlockingHooks()->onGetUserBlock(
				$this->getServiceContainer()->getUserFactory()->newFromUserIdentity( static::$testGloballyBlockedUser ),
				'127.0.0.2', $block
			),
			'::onGetUserBlock should always return true.'
		);
		// Validate the $block is a CompositeBlock
		$this->assertInstanceOf(
			CompositeBlock::class, $block, 'A CompositeBlock instance should be used if two blocks exist.'
		);
		// Validate that the CompositeBlock contains the expected original blocks
		$originalBlocks = $block->toArray();
		$this->assertCount( 2, $originalBlocks, 'The CompositeBlock should have two original blocks.' );
		$seenGlobalBlock = false;
		$seenSystemBlock = false;
		foreach ( $originalBlocks as $block ) {
			if ( $block instanceof GlobalBlock ) {
				$seenGlobalBlock = true;
			} elseif ( $block instanceof SystemBlock ) {
				$seenSystemBlock = true;
			}
		}
		$this->assertTrue( $seenGlobalBlock, 'The CompositeBlock should contain a GlobalBlock.' );
		$this->assertTrue( $seenSystemBlock, 'The CompositeBlock should contain a SystemBlock.' );
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
		// Create two test GlobalBlocks on an IP and IP range in the database for use in the above tests. These
		// should not be modified by any code in GlobalBlockingHooks, so this can be added once per-class.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUser();
		$testGloballyBlockedUser = $this->getMutableTestUser()->getUserIdentity();
		$hiddenUser = $this->getMutableTestUser()->getUserIdentity();
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4', 'Test reason', 'infinity', $testPerformer )
		);
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4/24', 'Test reason2', '1 month', $testPerformer )
		);
		$this->assertStatusGood( $globalBlockManager->block(
			$testGloballyBlockedUser->getName(), 'Test reason3', '3 days', $testPerformer
		) );
		$this->assertStatusGood( $globalBlockManager->block(
			$hiddenUser->getName(), 'Test reason4', '1 week', $testPerformer
		) );
		// Block the "hidden user" with 'hideuser' set to actually perform the hiding.
		$blockStatus = $this->getServiceContainer()->getBlockUserFactory()
			->newBlockUser(
				$hiddenUser, $this->getTestUser( [ 'sysop', 'suppress' ] )->getUser(),
				'infinity', 'block to hide the test user', [ 'isHideUser' => true ]
			)->placeBlockUnsafe();
		$this->assertStatusGood( $blockStatus );
		self::$testGloballyBlockedUser = $testGloballyBlockedUser;
		self::$hiddenUser = $hiddenUser;
		self::$unblockedUser = $this->getMutableTestUser()->getUserIdentity();
	}
}
