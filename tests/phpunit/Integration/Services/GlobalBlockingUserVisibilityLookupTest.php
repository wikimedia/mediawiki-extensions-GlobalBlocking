<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use CentralAuthTestUser;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\WikiMap\WikiMap;
use MediaWikiIntegrationTestCase;
use TestUserRegistry;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup
 * @group Database
 */
class GlobalBlockingUserVisibilityLookupTest extends MediaWikiIntegrationTestCase {

	use MockAuthorityTrait;

	public function testCheckAuthorityCanSeeUserForInvalidUsername() {
		$this->assertTrue(
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockingUserVisibilityLookup()
				->checkAuthorityCanSeeUser( 'Template:InvalidUsername#', $this->createMock( Authority::class ) ),
			'::checkAuthorityCanSeeUser should return true for an invalid username.'
		);
	}

	/** @dataProvider provideCheckAuthorityCanSeeUserForHiddenUser */
	public function testCheckAuthorityCanSeeUserForHiddenUser( $logViewerHasSuppress ) {
		// Block a test user with 'isHideUser' set to true to hide the user.
		$targetUser = $this->getMutableTestUser()->getUser();
		$blockStatus = $this->getServiceContainer()->getBlockUserFactory()
			->newBlockUser(
				$targetUser, $this->getTestUser( [ 'sysop', 'suppress' ] )->getUser(), 'infinity',
				'block to hide the test user', [ 'isHideUser' => true ]
			)->placeBlock();
		$this->assertStatusGood( $blockStatus );
		// Create an authority with the 'hideuser' permission if $logViewerHasSuppress is true, otherwise
		// create an authority without the 'hideuser' permission.
		if ( $logViewerHasSuppress ) {
			$logViewAuthority = $this->mockRegisteredAuthorityWithPermissions( [ 'hideuser' ] );
		} else {
			$logViewAuthority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'hideuser' ] );
		}
		// Call the method under test and verify that the actual result is the value of $logViewerHasSuppress.
		$this->assertSame(
			$logViewerHasSuppress,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockingUserVisibilityLookup()
				->checkAuthorityCanSeeUser( $targetUser->getName(), $logViewAuthority ),
			'::checkAuthorityCanSeeUser did not return the expected result.'
		);
	}

	public static function provideCheckAuthorityCanSeeUserForHiddenUser() {
		return [
			'User does not have suppress group' => [ false ],
			'User has suppress group' => [ true ]
		];
	}

	/** @dataProvider provideCheckAuthorityCanSeeUserForCentrallyHiddenUser */
	public function testCheckAuthorityCanSeeUserForCentrallyHiddenUser(
		$centralUserExists,
		$logViewerHasSuppress,
		$expectedReturnValue
	) {
		$this->markTestSkippedIfExtensionNotLoaded( 'CentralAuth' );
		// We need a new username for each test to avoid conflicts with tests.
		$targetUsername = 'GloballyHiddenUser' . TestUserRegistry::getNextId();
		if ( $centralUserExists ) {
			// Create a CentralAuth user with the username of the target and also set the hidden level to suppressed.
			$targetUser = new CentralAuthTestUser(
				$targetUsername, 'GUP@ssword',
				[
					'gu_id' => '3003',
					'gu_hidden_level' => CentralAuthUser::HIDDEN_LEVEL_SUPPRESSED,
				],
				[ [ WikiMap::getCurrentWikiId(), 'primary' ] ],
				false
			);
			$targetUser->save( $this->getDb() );
		}

		// Create an authority with the 'hideuser' permission if $logViewerHasCentralAuthSuppress is true, otherwise
		// create an authority without the 'hideuser' permission.
		if ( $logViewerHasSuppress ) {
			$logViewAuthority = $this->mockRegisteredAuthorityWithPermissions( [ 'centralauth-suppress' ] );
		} else {
			$logViewAuthority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'centralauth-suppress' ] );
		}
		// Call the method under test and verify that the actual result is the
		// value of $logViewerHasCentralAuthSuppress.
		$this->assertSame(
			$expectedReturnValue,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockingUserVisibilityLookup()
				->checkAuthorityCanSeeUser( $targetUsername, $logViewAuthority ),
			'::checkAuthorityCanSeeUser did not return the expected result.'
		);
	}

	public static function provideCheckAuthorityCanSeeUserForCentrallyHiddenUser() {
		return [
			'Central user does not exist' => [
				// Does a CentralAuth user exist with the username of the target
				false,
				// Does the authority have the centralauth-suppress right.
				true,
				// The expected return value
				true,
			],
			'User does not have steward group' => [ true, false, false ],
			'User has steward group' => [ true, true, true ]
		];
	}
}
