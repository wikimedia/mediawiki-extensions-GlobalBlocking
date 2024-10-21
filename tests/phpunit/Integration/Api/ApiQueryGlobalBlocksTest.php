<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Api;

use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Extension\GlobalBlocking\Api\ApiQueryGlobalBlocks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Api\Query\ApiQueryTestBase;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Api\ApiQueryGlobalBlocks
 */
class ApiQueryGlobalBlocksTest extends ApiQueryTestBase {

	use MockAuthorityTrait;

	private static UserIdentity $testPerformer;
	private static UserIdentity $testTarget;

	protected function setUp(): void {
		parent::setUp();
		// Fix the time for the tests to avoid the test blocks being expired.
		ConvertibleTimestamp::setFakeTime( '20230205060708' );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	/** @dataProvider provideExecuteProvidingIncompatibleParameters */
	public function testExecuteProvidingIncompatibleParameters( $params ) {
		// Validate the API errors out when incompatible parameters are provided.
		$this->expectApiErrorCode( 'invalidparammix' );
		$this->doApiRequest(
			array_merge(
				[ 'action' => 'query', 'list' => 'globalblocks' ],
				// Add the $params to the request with a mock value for each parameter.
				array_map(
					static function () {
						return 'test';
					},
					array_flip( $params )
				)
			)
		);
	}

	public static function provideExecuteProvidingIncompatibleParameters() {
		return [
			'ip and addresses parameters' => [ [ 'bgip', 'bgaddresses' ] ],
			'ip and targets parameters' => [ [ 'bgip', 'bgtargets' ] ],
			'targets and addresses parameters' => [ [ 'bgtargets', 'bgaddresses' ] ],
			'targets, ip, and addresses parameters' => [ [ 'bgtargets', 'bgip', 'bgaddresses' ] ],
		];
	}

	/** @dataProvider provideExecuteWithIPParamProvided */
	public function testExecuteWithIPParamProvided( $ip, $limit, $expectedBlockTargets ) {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgip' => $ip, 'bglimit' => $limit, 'bgprop' => 'address',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		// Assert that the expected number of global blocks were returned and that they are the expected ones.
		$this->assertSameSize( $expectedBlockTargets, $result['query']['globalblocks'] );
		foreach ( $result['query']['globalblocks'] as $block ) {
			$this->assertArrayEquals(
				[ 'address', 'anononly', 'account-creation-disabled', 'autoblocking-enabled', 'automatic' ],
				array_keys( $block ), false, false,
				'The properties returned by the API were not as expected'
			);
			$this->assertContains(
				$block['address'], array_keys( $expectedBlockTargets ),
				'The API returned a block that was not expected.'
			);
			$this->assertSame(
				$expectedBlockTargets[$block['address']]['anon-only'], $block['anononly'],
				'Anon only flag is not the expected value'
			);
			$this->assertSame(
				$expectedBlockTargets[$block['address']]['account-creation-disabled'],
				$block['account-creation-disabled'],
				'Anon only flag is not the expected value'
			);
			$this->assertSame(
				$expectedBlockTargets[$block['address']]['enable-autoblock'],
				$block['autoblocking-enabled'],
				'Autoblocking enabled flag is not the expected value'
			);
			$this->assertSame(
				$expectedBlockTargets[$block['address']]['automatic'],
				$block['automatic'],
				'Automatic flag is not the expected value'
			);
		}
	}

	public static function provideExecuteWithIPParamProvided() {
		return [
			'Single IPv4, limit 2' => [
				// The IP address or range to use as the 'bgip' parameter.
				'127.0.0.1',
				// The limit to use as the 'bglimit' parameter.
				2,
				// The expected block targets as keys and what block flags should be set as keys
				[
					'127.0.0.1' => [
						'anon-only' => true, 'account-creation-disabled' => true,
						'enable-autoblock' => false, 'automatic' => false,
					],
					'127.0.0.0/24' => [
						'anon-only' => false, 'account-creation-disabled' => true,
						'enable-autoblock' => false, 'automatic' => false,
					],
				],
			],
			'Single IPv4, limit 1' => [
				'127.0.0.1', 1,
				[
					'127.0.0.1' => [
						'anon-only' => true, 'account-creation-disabled' => true,
						'enable-autoblock' => false, 'automatic' => false,
					],
				],
			],
			'IPv4 range, limit 1' => [
				'127.0.0.0/25', 1,
				[
					'127.0.0.0/24' => [
						'anon-only' => false, 'account-creation-disabled' => true,
						'enable-autoblock' => false, 'automatic' => false,
					],
				],
			],
			'IPv6 range, limit 2' => [
				'2000:ABCD:ABCD:A:0:0:0:0/108', 2,
				[
					'2000:ABCD:ABCD:A:0:0:0:0/108' => [
						'anon-only' => false, 'account-creation-disabled' => false,
						'enable-autoblock' => false, 'automatic' => false,
					],
				],
			],
			'IPv4 that is only autoblocked' => [ '77.8.9.11', 2, [] ],
		];
	}

	public function testExecuteWithInvalidIPParam() {
		$this->expectApiErrorCode( 'invalidip' );
		$this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgip' => 'invalid',
		] );
	}

	public function testExecuteWithTooBroadIPParam() {
		$this->expectApiErrorCode( 'cidrtoobroad' );
		$this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgip' => '1.2.3.4/2',
		] );
	}

	/** @dataProvider provideExecuteForBlockThatHasExpired */
	public function testExecuteForBlockThatHasExpired( $paramName, $paramValue ) {
		// Validate that expired global blocks are not returned by testing no global blocks are returned when using the
		// $paramName and $paramValue to filter for just the expired block.
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', $paramName => $paramValue,
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount(
			0,
			$result['query']['globalblocks'],
			'No global blocks should have been returned as the block on the specified IP has already expired.'
		);
	}

	public static function provideExecuteForBlockThatHasExpired() {
		return [
			'With IP param' => [ 'bgip', '8.9.7.6' ],
			'With IDs param' => [ 'bgids', '6' ],
			'With targets param' => [ 'bgtargets', '8.9.7.6' ],
		];
	}

	public function testExecuteWithIdsParamWhereIdIsForRangeBlockWithAllProps() {
		// Validates the format of an example global block entry with all properties.
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgids' => '1',
			'bgprop' => 'id|target|by|timestamp|expiry|reason|range',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount( 1, $result['query']['globalblocks'] );
		$this->assertArrayEquals(
			[
				'id' => 1, 'target' => '127.0.0.0/24',
				'by' => static::$testPerformer->getName(), 'bywiki' => WikiMap::getCurrentWikiId(),
				'timestamp' => wfTimestamp( TS_ISO_8601, '20230205060708' ),
				'expiry' => wfTimestamp( TS_ISO_8601, '20250405060708' ),
				'reason' => 'test1', 'rangeend' => '127.0.0.255', 'rangestart' => '127.0.0.0',
				'account-creation-disabled' => true, 'anononly' => false,
				'autoblocking-enabled' => false, 'automatic' => false,
			],
			$result['query']['globalblocks'][0],
			false, true, 'The returned global block entry was not as expected.'
		);
	}

	/** @dataProvider provideExecuteForIdsLookupWithRangeProp */
	public function testExecuteForIdsLookupWithRangeProp( $id, $expectedArrayItem ) {
		// Validate that the 'range' prop works for both blocks on IPv4 and IPv6 addresses/ranges.
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgids' => $id,
			'bgprop' => 'id|target|range',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount( 1, $result['query']['globalblocks'] );
		$this->assertArrayEquals(
			$expectedArrayItem,
			$result['query']['globalblocks'][0],
			false, true, 'The returned global block entry was not as expected.'
		);
	}

	public static function provideExecuteForIdsLookupWithRangeProp() {
		return [
			'IPV4' => [
				'1',
				[
					'id' => 1, 'target' => '127.0.0.0/24', 'rangeend' => '127.0.0.255', 'rangestart' => '127.0.0.0',
					'account-creation-disabled' => true, 'anononly' => false, 'autoblocking-enabled' => false,
					'automatic' => false,
				],
			],
			'IPv6' => [
				'3',
				[
					'id' => 3, 'target' => '2000:ABCD:ABCD:A:0:0:0:0/108',
					'rangeend' => '2000:ABCD:ABCD:A:0:0:F:FFFF', 'rangestart' => '2000:ABCD:ABCD:A:0:0:0:0',
					'account-creation-disabled' => false, 'anononly' => false, 'autoblocking-enabled' => false,
					'automatic' => false,
				],
			],
			'IPv4 autoblock' => [
				'8',
				// The 'rangestart', 'rangeend', and 'target' props should not be returned, even if requested, when
				// the target is an autoblock.
				[
					'id' => '8', 'anononly' => false, 'autoblocking-enabled' => false,
					'account-creation-disabled' => true, 'automatic' => true,
				],
			],
		];
	}

	public function testExecuteWithDeprecatedAddressProp() {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgids' => '1',
			'bgprop' => 'address',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount( 1, $result['query']['globalblocks'] );
		$this->assertArrayEquals(
			[
				'address' => '127.0.0.0/24', 'account-creation-disabled' => true, 'anononly' => false,
				'autoblocking-enabled' => false, 'automatic' => false,
			],
			$result['query']['globalblocks'][0],
			false, true, 'The returned global block entry was not as expected.'
		);
	}

	public function testExecuteWithDeprecatedAddressPropForAutoblock() {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgids' => '8',
			'bgprop' => 'address',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount( 1, $result['query']['globalblocks'] );
		$this->assertArrayEquals(
			[
				'account-creation-disabled' => true, 'anononly' => false,
				'autoblocking-enabled' => false, 'automatic' => true,
			],
			$result['query']['globalblocks'][0],
			false, true,
			'The address property should not be included if the target is an autoblock'
		);
	}

	public function testExecuteForAutoblockWhenAPIConfiguredToHideAutoblocks() {
		$this->overrideConfigValue( 'GlobalBlockingHideAutoblocksInGlobalBlocksAPIResponse', true );
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgids' => '8',
			'bgprop' => 'target|range',
		] );
		// Assert that no global blocks are found when the API is configured to hide global autoblocks, as the ID
		// passed is for a global autoblock.
		$this->assertCount( 0, $result['query']['globalblocks'] );
	}

	/** @dataProvider provideExecuteWithTargetsParam */
	public function testExecuteWithTargetsParam( $targets, $limit, $expectedBlockIds ) {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgtargets' => $targets, 'bglimit' => $limit,
			'bgprop' => 'id',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		// Assert that the expected number of global blocks were returned and that they are the expected ones.
		$this->assertSameSize( $expectedBlockIds, $result['query']['globalblocks'] );
		foreach ( $result['query']['globalblocks'] as $key => $block ) {
			$this->assertSame(
				$block['id'], $expectedBlockIds[$key],
				'The API returned a block that was not expected.'
			);
			// Assert that only the id field was returned, as requested.
			$this->assertArrayEquals(
				[ 'id' ], array_keys( $block ), false, false,
				'The properties returned by the API were not as expected'
			);
		}
	}

	public static function provideExecuteWithTargetsParam() {
		return [
			'One IP and one IPv4 range, limit 2' => [
				// The IP addresses / ranges to use as the 'bgtargets' parameter.
				'127.0.0.1|127.0.0.0/24',
				// The limit to use as the 'bglimit' parameter.
				2,
				// The expected block IDs
				[ '4', '1' ],
			],
			'Single IPv4, limit 1' => [ '127.0.0.1', 1, [ '4' ] ],
			'IPv4 range, limit 1' => [ '127.0.0.0/24', 1, [ '1' ] ],
			'IPv6 range, limit 2' => [ '2000:ABCD:ABCD:A:0:0:0:0/108', 2, [ '3' ] ],
			'IPv4 that is only autoblocked' => [ '77.8.9.11', 2, [] ],
		];
	}

	public function testExecuteWithTargetsParamForBlockedUsernameAndIP() {
		$this->testExecuteWithTargetsParam( static::$testTarget->getName() . '|127.0.0.1', 2, [ '7', '4' ] );
	}

	public function testExecuteWithTargetsParamForBlockedUsername() {
		$this->testExecuteWithTargetsParam( static::$testTarget->getName(), 2, [ '7' ] );
	}

	public function testExecuteWithTargetsParamWithNonExistingUsername() {
		$this->testExecuteWithTargetsParam( 'NonExistingUsername', 1, [] );
	}

	public function testExecuteWithAddressesParamWithInvalidUser() {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgaddresses' => 'Template:Test#test',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		// Assert that no global blocks are found for an invalid username target.
		$this->assertCount( 0, $result['query']['globalblocks'] );
	}

	public function testGetExamplesMessages() {
		// Test that all the items in ::getExamplesMessages have keys which is a string and values which are valid
		// message keys.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$main = new ApiMain( $this->apiContext, true );
		/** @var ApiQuery $query */
		$query = $main->getModuleManager()->getModule( 'query' );
		$apiQueryGlobalBlocksModule = new ApiQueryGlobalBlocks(
			$query, 'globalblock',
			$this->getServiceContainer()->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider()
		);
		$apiQueryGlobalBlocksModule = TestingAccessWrapper::newFromObject( $apiQueryGlobalBlocksModule );
		$examplesMessages = $apiQueryGlobalBlocksModule->getExamplesMessages();
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

	public function addDBDataOnce() {
		// Allow global autoblocks, so that we can check that global autoblocks are properly handled by the API
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
		// We can add the DB data once for this class as the service should not modify, insert or delete rows from
		// the database.
		$testPerformer = $this->getTestUser( [ 'sysop', 'steward' ] )->getUserIdentity();
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockManager = $globalBlockingServices->getGlobalBlockManager();
		// Insert some range blocks
		//
		// Fix the time for the tests to avoid the test blocks being expired, but use a different timestamp for each
		// block to test sorting.
		ConvertibleTimestamp::setFakeTime( '20230205060708' );
		$globalBlockManager->block( '127.0.0.0/24', 'test1', '20250405060708', $testPerformer );
		ConvertibleTimestamp::setFakeTime( '20230205060709' );
		$globalBlockManager->block( '88.8.9.0/24', 'test2', '20240505060708', $testPerformer );
		ConvertibleTimestamp::setFakeTime( '20230205060710' );
		$globalBlockManager->block(
			'2000:ABCD:ABCD:A:0:0:0:0/108', 'testipv6', '20240505060708', $testPerformer,
			[ 'allow-account-creation' ]
		);
		// Insert some single IP blocks
		ConvertibleTimestamp::setFakeTime( '20230205060711' );
		$globalBlockManager->block( '127.0.0.1', 'test3', '20240605060708', $testPerformer, [ 'anon-only' ] );
		ConvertibleTimestamp::setFakeTime( '20230205060712' );
		$globalBlockManager->block( '77.8.9.10', 'test4', '20240405060708', $testPerformer );
		// Insert a IP block that should not be displayed in the results ever because it is expired
		ConvertibleTimestamp::setFakeTime( '20230105060712' );
		$globalBlockManager->block( '8.9.7.6', 'test4', '20230203060708', $testPerformer );
		// Insert a global block on a username target
		ConvertibleTimestamp::setFakeTime( '20230205060713' );
		$testUser = $this->getMutableTestUser()->getUserIdentity();
		$userBlockStatus = $globalBlockManager->block(
			$testUser->getName(), 'test5', '20240705060708', $testPerformer,
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $userBlockStatus );
		$userBlockId = $userBlockStatus->getValue()['id'];
		// Insert an autoblock for the global block on the username target
		$globalBlockManager->autoblock( $userBlockId, '77.8.9.11' );
		// Store the $testPerformer for later use
		static::$testPerformer = $testPerformer;
		static::$testTarget = $testUser;
		// Reset the fake time before returning to avoid issues. It will be set to a fake time again in ::setUp.
		ConvertibleTimestamp::setFakeTime( false );
	}
}
