<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Api;

use ApiMain;
use ApiQuery;
use MediaWiki\Extension\GlobalBlocking\Api\ApiQueryGlobalBlocks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
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

	protected function setUp(): void {
		parent::setUp();
		// Fix the time for the tests to avoid the test blocks being expired.
		ConvertibleTimestamp::setFakeTime( '20230205060708' );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
	}

	public function testExecuteProvidingBothAddressesAndIP() {
		$this->expectApiErrorCode( 'invalidparammix' );
		$this->doApiRequest(
			[ 'action' => 'query', 'list' => 'globalblocks', 'bgip' => '1.2.3.4/24', 'bgaddresses' => '1.2.3.4' ]
		);
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
			$this->assertContains(
				$block['address'], array_keys( $expectedBlockTargets ),
				'The API returned a block that was not expected.'
			);
			// Assert that only the address field was returned, as requested.
			// The exception to this is if the block is anon-only, where an additional field is returned.
			$expectedFields = [ 'address' ];
			if ( $expectedBlockTargets[$block['address']] ) {
				$expectedFields[] = 'anononly';
			}
			$this->assertArrayEquals(
				$expectedFields, array_keys( $block ), false, false,
				'The properties returned by the API were not as expected'
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
				// The expected block targets as keys and whether they are expected to be anon-only as values.
				[ '127.0.0.1' => true, '127.0.0.0/24' => false ],
			],
			'Single IPv4, limit 1' => [ '127.0.0.1', 1, [ '127.0.0.1' => true ] ],
			'IPv4 range, limit 1' => [ '127.0.0.0/25', 1, [ '127.0.0.0/24' => false ] ],
			'IPv6 range, limit 2' => [ '2000:ABCD:ABCD:A:0:0:0:0/108', 2, [ '2000:ABCD:ABCD:A:0:0:0:0/108' => false ] ],
		];
	}

	public function testExecuteWithInvalidIPParam() {
		$this->expectApiErrorCode( 'param_ip' );
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

	public function testExecuteForIPParamWhenBlockHasAlreadyExpired() {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgip' => '8.9.7.6'
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount(
			0,
			$result['query']['globalblocks'],
			'No global blocks should have been returned as the block on the specified IP has already expired.'
		);
	}

	public function testExecuteWithIdsParamWhereIdIsForRangeBlockWithAllProps() {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgids' => '1',
			'bgprop' => 'id|address|by|timestamp|expiry|reason|range',
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount( 1, $result['query']['globalblocks'] );
		$this->assertArrayEquals(
			[
				'id' => 1, 'address' => '127.0.0.0/24',
				'by' => static::$testPerformer->getName(), 'bywiki' => WikiMap::getCurrentWikiId(),
				'timestamp' => wfTimestamp( TS_ISO_8601, '20230205060708' ),
				'expiry' => wfTimestamp( TS_ISO_8601, '20250405060708' ),
				'reason' => 'test1', 'rangeend' => '127.0.0.255', 'rangestart' => '127.0.0.0',
			],
			$result['query']['globalblocks'][0],
			false, true, 'The returned global block entry was not as expected.'
		);
	}

	/** @dataProvider provideExecuteWithAddressesParam */
	public function testExecuteWithAddressesParam( $addresses, $limit, $expectedBlockIds ) {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgaddresses' => $addresses, 'bglimit' => $limit,
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

	public static function provideExecuteWithAddressesParam() {
		return [
			'One IP and one IPv4 range, limit 2' => [
				// The IP addresses / ranges to use as the 'bgaddresses' parameter.
				'127.0.0.1|127.0.0.0/24',
				// The limit to use as the 'bglimit' parameter.
				2,
				// The expected block IDs
				[ '4', '1' ],
			],
			'Single IPv4, limit 1' => [ '127.0.0.1', 1, [ '4' ] ],
			'IPv4 range, limit 1' => [ '127.0.0.0/24', 1, [ '1' ] ],
			'IPv6 range, limit 2' => [ '2000:ABCD:ABCD:A:0:0:0:0/108', 2, [ '3' ] ],
		];
	}

	public function testExecuteWithInvalidAddressesParam() {
		$this->expectApiErrorCode( 'param_addresses' );
		$this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgaddresses' => 'invalid',
		] );
	}

	public function testExecuteForAddressesParamWhenBlockHasAlreadyExpired() {
		[ $result ] = $this->doApiRequest( [
			'action' => 'query', 'list' => 'globalblocks', 'bgaddresses' => '8.9.7.6'
		] );
		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'globalblocks', $result['query'] );
		$this->assertCount(
			0,
			$result['query']['globalblocks'],
			'No global blocks should have been returned as the block on the specified IP has already expired.'
		);
	}

	public function testGetExamplesMessages() {
		// Test that all the items in ::getExamplesMessages have keys which is a string and values which are valid
		// message keys.
		$main = new ApiMain( $this->apiContext, true );
		/** @var ApiQuery $query */
		$query = $main->getModuleManager()->getModule( 'query' );
		$apiQueryGlobalBlocksModule = new ApiQueryGlobalBlocks(
			$query, 'globalblock',
			$this->getServiceContainer()->getCentralIdLookup()
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
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
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
		$globalBlockManager->block( '2000:ABCD:ABCD:A:0:0:0:0/108', 'testipv6', '20240505060708', $testPerformer );
		// Insert some single IP blocks
		ConvertibleTimestamp::setFakeTime( '20230205060711' );
		$globalBlockManager->block( '127.0.0.1', 'test3', '20240605060708', $testPerformer, [ 'anon-only' ] );
		ConvertibleTimestamp::setFakeTime( '20230205060712' );
		$globalBlockManager->block( '77.8.9.10', 'test4', '20240405060708', $testPerformer );
		// Insert a IP block that should not be displayed in the results ever because it is expired
		ConvertibleTimestamp::setFakeTime( '20230105060712' );
		$globalBlockManager->block( '8.9.7.6', 'test4', '20230203060708', $testPerformer );
		// Store the $testPerformer for later use
		static::$testPerformer = $testPerformer;
	}
}
