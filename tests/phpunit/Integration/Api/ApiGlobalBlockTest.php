<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Api;

use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiResult;
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

	/** @dataProvider provideInvalidCombinationsOfParameters */
	public function testInvalidCombinationsOfParameters( $parameters, $expectedErrorCode ) {
		$this->expectApiErrorCode( $expectedErrorCode );
		$this->doApiRequestWithToken( $parameters, null, $this->getAuthorityForSuccess() );
	}

	public static function provideInvalidCombinationsOfParameters() {
		return [
			'Missing both target and id' => [ [ 'action' => 'globalblock' ], 'missingparam' ],
			'Missing both expiry and unblock' => [
				[ 'action' => 'globalblock', 'target' => '1.2.3.4' ], 'missingparam',
			],
		];
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
			[
				'action' => 'globalblock', 'target' => $target, 'reason' => 'test', 'expiry' => '1 month',
				'allow-account-creation' => 0,
			],
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
			'The block should not have been modified by the API call.'
		);
	}

	/** @dataProvider provideIPBlockTargets */
	public function testExecuteForEnablingAutoblocksOnNonUserBlock( $target ) {
		// Call the API to block the target
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'target' => $target, 'reason' => 'test', 'expiry' => '1 month',
				'enable-autoblock' => 1,
			],
			null, $this->getAuthorityForSuccess()
		);
		// Verify that the API response indicates that only user blocks can trigger autoblocks
		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'globalblock', $result['error'] );
		$this->assertCount( 1, $result['error']['globalblock'] );
		$this->assertArrayHasKey( 'code', $result['error']['globalblock'][0] );
		$this->assertSame(
			'globalblocking-block-enable-autoblock-on-ip',
			$result['error']['globalblock'][0]['code'],
			'The error code was not as expected.'
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
			'The response was not as expected and suggests that the target was not successfully blocked.'
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
		// Check that autoblocking has not been enabled, as it was not requested.
		$this->assertSame( '0', $actualBlock->gb_enable_autoblock );
		// Verify that there is an active local block on the target
		$actualLocalBlock = $this->getServiceContainer()->getDatabaseBlockStore()->newFromTarget( $target );
		$this->assertNotNull( $actualLocalBlock, 'A local block should have been performed' );
		$this->assertFalse( $actualLocalBlock->isCreateAccountBlocked() );
	}

	public function testExecuteForBlockModificationSpecifyingId() {
		// Block a target
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockStatus = $globalBlockingServices->getGlobalBlockManager()->block(
			'1.2.3.7', 'block to be modified', '1 day', $this->getAuthorityForSuccess()->getUser()
		);
		$this->assertStatusGood( $globalBlockStatus );
		// Modify the global block using the global block ID
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'id' => $globalBlockStatus->getValue()['id'],
				'reason' => 'test', 'modify' => '1', 'anononly' => 1, 'expiry' => 'infinity',
			],
			null, $this->getAuthorityForSuccess()
		);
		// Assert that the response of the API is that the target has been unblocked.
		$this->assertArrayHasKey( 'globalblock', $result );
		$this->assertArrayEquals(
			[
				'user' => '#' . $globalBlockStatus->getValue()['id'], 'blocked' => '', 'anononly' => '',
				'expiry' => 'infinite',
			],
			$result['globalblock'], false, true,
			'The response was not as expected and suggests that the target was not successfully modified.'
		);
		// Assert that the block has been successfully modified
		$this->newSelectQueryBuilder()
			->select( [ 'gb_anon_only', 'gb_address' ] )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $globalBlockStatus->getValue()['id'] ] )
			->assertRowValue( [ '1', '1.2.3.7' ] );
	}

	public function testExecuteForBlockModificationSpecifyingGlobalAutoblockId() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		// Perform a block on a test user and then perform a global autoblock on an IP using the global user block
		// as the parent block.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$globalAccountBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test1234', 'infinite',
			$this->getTestUser( [ 'steward' ] )->getUser(), [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalAccountBlockStatus );
		$accountGlobalBlockId = $globalAccountBlockStatus->getValue()['id'];
		$autoBlockStatus = $globalBlockManager->autoblock( $accountGlobalBlockId, '7.8.9.0' );
		$this->assertStatusGood( $autoBlockStatus );
		$autoBlockId = $autoBlockStatus->getValue()['id'];
		// Attempt to modify the global autoblock and expect that this fails
		$this->expectApiErrorCode( 'cannot-modify-global-autoblock' );
		$this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'id' => $autoBlockId, 'reason' => 'test', 'modify' => '1',
				'anononly' => 1, 'expiry' => 'infinity',
			],
			null, $this->getAuthorityForSuccess()
		);
	}

	/** @dataProvider provideUserBlockTargets */
	public function testExecuteForUserBlockWhenAutoblockingEnabled( $target ) {
		// Fix the time to ensure that the provided expiry remains the same between test runs
		ConvertibleTimestamp::setFakeTime( '20200305060708' );
		// Call the API to block the target
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'target' => $target, 'reason' => 'test', 'expiry' => '1 month',
				'enable-autoblock' => 1,
			],
			null, $this->getAuthorityForSuccess()
		);
		$this->assertArrayHasKey( 'globalblock', $result );
		$this->assertArrayEquals(
			[
				'user' => $target, 'blocked' => '',
				'expiry' => ApiResult::formatExpiry( '20200405060708' ), 'enable-autoblock' => '',
			],
			$result['globalblock'], false, true,
			'The response was not as expected and suggests that the target was not successfully blocked.'
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
		// Check that autoblocking has been enabled.
		$this->assertSame( '1', $actualBlock->gb_enable_autoblock );
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

	public function testExecuteForUnblockSpecifyingId() {
		// Block a target
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockStatus = $globalBlockingServices->getGlobalBlockManager()->block(
			'1.2.3.7', 'block to be removed', '1 day', $this->getAuthorityForSuccess()->getUser()
		);
		$this->assertStatusGood( $globalBlockStatus );
		// Unblock the target using the API specifying the ID for the global block
		[ $result ] = $this->doApiRequestWithToken(
			[
				'action' => 'globalblock', 'id' => $globalBlockStatus->getValue()['id'],
				'reason' => 'test', 'unblock' => '1',
			],
			null, $this->getAuthorityForSuccess()
		);
		// Assert that the response of the API is that the target has been unblocked.
		$this->assertArrayHasKey( 'globalblock', $result );
		$this->assertArrayEquals(
			[ 'user' => '#' . $globalBlockStatus->getValue()['id'], 'unblocked' => '' ],
			$result['globalblock'], false, true,
			'The response was not as expected and suggests that the target was not successfully unblocked.'
		);
		// Assert that no block now exists for this target
		$this->assertSame( 0, $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockId( '1.2.3.7' ) );
	}

	public function testGetExamplesMessages() {
		// Test that all the items in ::getExamplesMessages have keys which is a string and values which are valid
		// message keys.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$apiGlobalBlockModule = new ApiGlobalBlock(
			new ApiMain( $this->apiContext, true ),
			'globalblock',
			$this->getServiceContainer()->getBlockUserFactory(),
			$globalBlockingServices->getGlobalBlockManager(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider()
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
