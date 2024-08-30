<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Api;

use ApiMain;
use ApiResult;
use MediaWiki\Extension\GlobalBlocking\Api\ApiGlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Permissions\Authority;
use MediaWiki\Tests\Api\ApiTestCase;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use TestUser;
use Wikimedia\IPUtils;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Api\ApiGlobalBlock
 */
class ApiGlobalBlockTest extends ApiTestCase {

	use MockAuthorityTrait;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	private function getAuthorityForSuccess(): Authority {
		return $this->getTestUser( [ 'steward' ] )->getAuthority();
	}

	public function testExecuteMissingExpiryAndUnblock() {
		$this->expectApiErrorCode( 'missingparam' );
		$this->doApiRequestWithToken(
			[ 'action' => 'globalblock', 'target' => '1.2.3.4' ], null, $this->getAuthorityForSuccess()
		);
	}

	public static function provideIPBlockTargets() {
		return [
			'IP target' => [ '1.2.3.4' ],
			'IP range target' => [ '5.4.3.0/24' ],
		];
	}

	public static function provideUserBlockTargets() {
		return [
			'User target' => [ 'GlobalBlockingTestTarget' ],
		];
	}

	/**
	 * @dataProvider provideIPBlockTargets
	 * @dataProvider provideUserBlockTargets
	 */
	public function testExecuteForBlockWithExistingBlockButNotSetToModify( $target ) {
		// Block the target
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockingServices->getGlobalBlockManager()->block(
			$target, 'test block', '1 day', $this->getAuthorityForSuccess()->getUser()
		);
		// Call the API to block the target
		[ $result ] = $this->doApiRequestWithToken(
			[ 'action' => 'globalblock', 'target' => $target, 'reason' => 'test', 'expiry' => '1 month' ],
			null, $this->getAuthorityForSuccess()
		);
		// Verify that the API response indicates that the target was already blocked.
		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'globalblock', $result['error'] );
		$this->assertCount( 1, $result['error']['globalblock'] );
		$this->assertArrayHasKey( 'code', $result['error']['globalblock'][0] );
		$this->assertSame(
			'globalblocking-block-alreadyblocked',
			$result['error']['globalblock'][0]['code'],
			'The error code was not as expected.'
		);
		if ( IPUtils::isIPAddress( $target ) ) {
			$actualBlock = $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockingBlock( $target, 0 );
		} else {
			$actualBlock = $globalBlockingServices->getGlobalBlockLookup()
				->getGlobalBlockingBlock(
					null,
					$this->getServiceContainer()->getCentralIdLookup()->centralIdFromName( $target )
				);
		}
		$this->assertNotNull( $actualBlock );
		$this->assertSame(
			'1',
			$actualBlock->gb_create_account,
			'The block was not correctly updated by the API call.'
		);
	}

	/** @dataProvider provideIPBlockTargets */
	public function testExecuteForBlockWithAnonOnlyAndModifySet( $target ) {
		// Fix the time to ensure that the provided expiry is always in the future.
		ConvertibleTimestamp::setFakeTime( '20200305060708' );
		// Block the target
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockingServices->getGlobalBlockManager()->block(
			$target, 'test block', '20200405060708', $this->getAuthorityForSuccess()->getUser()
		);
		// Call the API to block the target
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'target' => $target, 'reason' => 'test',
				'expiry' => '20200505060708', 'modify' => '1', 'anononly' => '1'
			],
			null, $this->getAuthorityForSuccess()
		);
		// Verify that API response indicates that the re-block succeeded
		$this->assertArrayHasKey( 'globalblock', $result );
		$this->assertArrayEquals(
			[
				'user' => $target, 'blocked' => '', 'anononly' => '',
				'expiry' => ApiResult::formatExpiry( '20200505060708' ),
			],
			$result['globalblock'], false, true,
			'The response was not as expected and suggests that the target was not successfully unblocked.'
		);
		// Verify that the active block on the target is set to anon-only (and therefore was modified by the API call).
		$actualBlock = $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockingBlock( $target, 0 );
		$this->assertSame(
			'1',
			$actualBlock->gb_anon_only,
			'The block was not correctly updated by the API call.'
		);
	}

	/**
	 * @dataProvider provideIPBlockTargets
	 * @dataProvider provideUserBlockTargets
	 */
	public function testExecuteForBlockWithLocalBlockButHasExistingLocalBlock( $target ) {
		// Fix the time to ensure that the provided expiry is always in the future.
		ConvertibleTimestamp::setFakeTime( '20200305060708' );
		$this->getServiceContainer()->getBlockUserFactory()->newBlockUser(
			$target, $this->getTestUser( [ 'steward', 'sysop' ] )->getAuthority(), '20200405060708', 'test block'
		)->placeBlock();
		// Call the API to block the target
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'target' => $target, 'reason' => 'test',
				'expiry' => '20200505060708', 'alsolocal' => '1', 'localblocksemail' => '1',
				'localblockstalk' => '1',
			],
			// The user needs to have the sysop and steward groups to locally block while performing a global block.
			null, $this->getTestUser( [ 'steward', 'sysop' ] )->getAuthority()
		);
		// Verify that the API response indicates that the target was already blocked.
		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'globalblock', $result['error'] );
		$this->assertCount( 1, $result['error']['globalblock'] );
		$this->assertArrayHasKey( 'code', $result['error']['globalblock'][0] );
		$this->assertSame(
			'ipb_already_blocked',
			$result['error']['globalblock'][0]['code'],
			'The error code was not as expected.'
		);
	}

	/**
	 * @dataProvider provideIPBlockTargets
	 * @dataProvider provideUserBlockTargets
	 */
	public function testExecuteForBlockWithLocalBlock( $target ) {
		// Fix the time to ensure that the provided expiry is always in the future.
		ConvertibleTimestamp::setFakeTime( '20200305060708' );
		// Call the API to block the target
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'target' => $target, 'reason' => 'test',
				'expiry' => '20200505060708', 'alsolocal' => '1', 'localblocksemail' => '1',
				'localblockstalk' => '1', 'localanononly' => IPUtils::isIPAddress( $target ) ? '1' : '',
				'allow-account-creation' => 1, 'local-allow-account-creation' => 1
			],
			// The user needs to have the sysop and steward groups to locally block while performing a global block.
			null, $this->getTestUser( [ 'steward', 'sysop' ] )->getAuthority()
		);
		// Verify that API response indicates that the re-block succeeded
		$this->assertArrayHasKey( 'globalblock', $result );
		$this->assertArrayEquals(
			[
				'user' => $target, 'blocked' => '',
				'expiry' => ApiResult::formatExpiry( '20200505060708' ),
				'blockedlocally' => true, 'allow-account-creation' => '',
			],
			$result['globalblock'], false, true,
			'The response was not as expected and suggests that the target was not successfully unblocked.'
		);
		// Verify that there is an active global block on the target
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		if ( IPUtils::isIPAddress( $target ) ) {
			$actualBlock = $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockingBlock( $target, 0 );
		} else {
			$actualBlock = $globalBlockingServices->getGlobalBlockLookup()
				->getGlobalBlockingBlock(
					null,
					$this->getServiceContainer()->getCentralIdLookup()->centralIdFromName( $target )
				);
		}
		$this->assertNotNull( $actualBlock );
		$this->assertSame(
			'0',
			$actualBlock->gb_create_account,
			'The block was not correctly updated by the API call.'
		);
		// Verify that there is an active local block on the target
		$actualLocalBlock = $this->getServiceContainer()->getDatabaseBlockStore()->newFromTarget( $target );
		$this->assertNotNull( $actualLocalBlock, 'A local block should have been performed' );
		$this->assertFalse( $actualLocalBlock->isCreateAccountBlocked() );
	}

	/**
	 * @dataProvider provideIPBlockTargets
	 * @dataProvider provideUserBlockTargets
	 */
	public function testExecuteForUnblockWhenUserNotBlocked( $target ) {
		// Call the API to unblock the target
		[ $result ] = $this->doApiRequestWithToken(
			[ 'action' => 'globalblock', 'target' => $target, 'reason' => 'test', 'unblock' => '1' ],
			null, $this->getAuthorityForSuccess()
		);
		// Verify that the API responds with an error indicating that the target was not blocked.
		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'globalblock', $result['error'] );
		$this->assertCount( 1, $result['error']['globalblock'] );
		$this->assertArrayHasKey( 'code', $result['error']['globalblock'][0] );
		$this->assertSame(
			'globalblocking-notblocked',
			$result['error']['globalblock'][0]['code'],
			'The error code was not as expected.'
		);
	}

	/**
	 * @dataProvider provideIPBlockTargets
	 * @dataProvider provideUserBlockTargets
	 */
	public function testExecuteForUnblock( $target ) {
		// Block the target
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockingServices->getGlobalBlockManager()->block(
			$target, 'block to be removed', '1 day', $this->getAuthorityForSuccess()->getUser()
		);
		// Unblock the target using the API.
		[ $result ] = $this->doApiRequestWithToken(
			[ 'action' => 'globalblock', 'target' => $target, 'reason' => 'test', 'unblock' => '1' ],
			null, $this->getAuthorityForSuccess()
		);
		// Assert that the response of the API is that the target has been unblocked.
		$this->assertArrayHasKey( 'globalblock', $result );
		$this->assertArrayEquals(
			[ 'user' => $target, 'unblocked' => '' ], $result['globalblock'], false, true,
			'The response was not as expected and suggests that the target was not successfully unblocked.'
		);
		// Assert that no block now exists for this target
		$this->assertSame( 0, $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockId( $target ) );
	}

	public function testGetExamplesMessages() {
		// Test that all the items in ::getExamplesMessages have keys which is a string and values which are valid
		// message keys.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$apiGlobalBlockModule = new ApiGlobalBlock(
			new ApiMain( $this->apiContext, true ),
			'globalblock',
			$this->getServiceContainer()->getBlockUserFactory(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockManager(),
			$this->getServiceContainer()->getCentralIdLookup()
		);
		$apiGlobalBlockModule = TestingAccessWrapper::newFromObject( $apiGlobalBlockModule );
		$examplesMessages = $apiGlobalBlockModule->getExamplesMessages();
		foreach ( $examplesMessages as $query => $messageKey ) {
			$this->assertIsString(
				$query,
				'The URL query string was not as expected.'
			);
			$this->assertTrue(
				wfMessage( $messageKey )->exists(),
				"The message key $messageKey does not exist."
			);
		}
	}

	public function addDBData() {
		new TestUser( 'GlobalBlockingTestTarget' );
	}
}
