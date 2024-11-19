<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\WikiMap\WikiMap;
use MediaWikiIntegrationTestCase;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\ReadOnlyMode;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingBlockPurger
 * @group Database
 */
class GlobalBlockingBlockPurgerTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider providePurgeExpiredBlocks */
	public function testPurgeExpiredBlocks(
		$target, $fakeTime, $expectedTargetsAfterPurge, $expectedGlobalBlockWhitelistCount, $limit
	) {
		// Set a fake time such that the expiry of all blocks is after this date.
		ConvertibleTimestamp::setFakeTime( $fakeTime );
		$this->overrideConfigValue( MainConfigNames::UpdateRowsPerQuery, $limit );
		// Call the method under test
		GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingBlockPurger()
			->purgeExpiredBlocks( $target );
		// Check that the correct rows were purged
		$this->assertArrayEquals(
			$expectedTargetsAfterPurge,
			$this->getDb()->newSelectQueryBuilder()
				->select( 'gb_address' )
				->from( 'globalblocks' )
				->fetchFieldValues(),
			false,
			false,
			'The globalblocks table is not as expected after the purge.'
		);
		$this->assertSame(
			$expectedGlobalBlockWhitelistCount,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'global_block_whitelist' )
				->fetchField(),
			'The global_block_whitelist table has an unexpected number of rows after calling the purge method.'
		);
	}

	public static function providePurgeExpiredBlocks() {
		return [
			'No blocks to purge' => [
				// The value of the $target parameter
				null,
				// The time to be set for the test
				'20220405060708',
				// What rows should be left in the table after the purge
				[ '127.0.0.0/24', '127.0.0.1', '127.0.0.2' ],
				// How many rows should be left in the global_block_whitelist table after the test
				1,
				// The limit to be passed to the method under test
				1000,
			],
			'One block to purge' => [ null, '20240505060708', [ '127.0.0.0/24', '127.0.0.2' ], 1, 1000 ],
			'All blocks have expired' => [ null, '20250405060708', [], 0, 1000 ],
			'All blocks have expired, but UpdateRowsPerQuery is 1' => [
				null, '20260505060708', [ '127.0.0.0/24', '127.0.0.2' ], 0, 1,
			],
			'All blocks have expired, UpdateRowsPerQuery is 1, target set as the /24' => [
				'127.0.0.0/24', '20260505060708', [ '127.0.0.1', '127.0.0.2' ], 0, 1,
			],
			'All blocks have expired, UpdateRowsPerQuery is 1, target set as 127.0.0.1' => [
				'127.0.0.1', '20260505060708',
				// 127.0.0.2 is not expected here, even though the limit is 1, because any autoblock attached
				// to the target block should be dropped too irrespective of the UpdateRowsPerQuery limit
				[ '127.0.0.0/24' ],
				0, 1,
			],
		];
	}

	/** @dataProvider provideReadOnlyModeSettings */
	public function testPurgeExpiredBlocksWhenInReadOnlyMode( $centralDbInReadOnlyMode, $localDbInReadOnlyMode ) {
		$mockReadOnlyMode = $this->createMock( ReadOnlyMode::class );
		$mockReadOnlyMode->method( 'isReadOnly' )
			->willReturnCallback( static function ( $domain ) use ( $centralDbInReadOnlyMode, $localDbInReadOnlyMode ) {
				if ( $domain === false ) {
					return $localDbInReadOnlyMode;
				}
				return $centralDbInReadOnlyMode;
			} );
		$this->setService( 'ReadOnlyMode', static function () use ( $mockReadOnlyMode ) {
			return $mockReadOnlyMode;
		} );
		$this->testPurgeExpiredBlocks(
			null,
			'20260505060708',
			$centralDbInReadOnlyMode ? [ '127.0.0.1', '127.0.0.0/24', '127.0.0.2' ] : [],
			$localDbInReadOnlyMode ? 1 : 0,
			1000
		);
	}

	public static function provideReadOnlyModeSettings() {
		return [
			'Both databases are in read-only mode' => [ true, true ],
			'Central database is in read-only mode' => [ true, false ],
			'Local database is in read-only mode' => [ false, true ],
		];
	}

	public function addDBData() {
		$testUser = $this->getTestSysop()->getUserIdentity();
		// Insert a range block and single IP block for the test.
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'globalblocks' )
			->row( [
				'gb_address' => '127.0.0.1',
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testUser ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20230405060708' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->encodeExpiry( '20240405060708' ),
				'gb_range_start' => IPUtils::toHex( '127.0.0.1' ),
				'gb_range_end' => IPUtils::toHex( '127.0.0.1' ),
				'gb_autoblock_parent_id' => 0,
			] )
			->row( [
				'gb_address' => '127.0.0.0/24',
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testUser ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20220405060708' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->encodeExpiry( '20250405060708' ),
				'gb_range_start' => IPUtils::toHex( '127.0.0.0' ),
				'gb_range_end' => IPUtils::toHex( '127.0.0.255' ),
				'gb_autoblock_parent_id' => 0,
			] )
			->row( [
				'gb_address' => '127.0.0.2',
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testUser ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20230405060708' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->encodeExpiry( '20240605060708' ),
				'gb_range_start' => IPUtils::toHex( '127.0.0.2' ),
				'gb_range_end' => IPUtils::toHex( '127.0.0.2' ),
				'gb_autoblock_parent_id' => 1,
			] )
			->caller( __METHOD__ )
			->execute();
		// Insert a whitelist entry for the range block
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'global_block_whitelist' )
			->row( [
				'gbw_by' => $testUser->getId(),
				'gbw_by_text' => $testUser->getName(),
				'gbw_reason' => 'test-override',
				'gbw_expiry' => $this->getDb()->encodeExpiry( '20250405060708' ),
				'gbw_id' => $this->newSelectQueryBuilder()
					->select( 'gb_id' )
					->from( 'globalblocks' )
					->where( [ 'gb_address' => '127.0.0.0/24' ] )
					->fetchField(),
			] )
			->caller( __METHOD__ )
			->execute();
	}
}
