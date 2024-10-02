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
		$fakeTime, $expectedGlobalBlocksCount, $expectedGlobalBlockWhitelistCount, $limit
	) {
		// Set a fake time such that the expiry of all blocks is after this date.
		ConvertibleTimestamp::setFakeTime( $fakeTime );
		$this->overrideConfigValue( MainConfigNames::UpdateRowsPerQuery, $limit );
		// Call the method under test
		GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingBlockPurger()
			->purgeExpiredBlocks();
		// Check that no rows were deleted
		$this->assertSame(
			$expectedGlobalBlocksCount,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'globalblocks' )
				->fetchField(),
			'The globalblocks table has an unexpected number of rows after calling the purge method.'
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
				// The time to be set for the test
				'20220405060708',
				// How many rows should be left in the globalblocks table after the test
				2,
				// How many rows should be left in the global_block_whitelist table after the test
				1,
				// The limit to be passed to the method under test
				1000,
			],
			'One block to purge' => [ '20240505060708', 1, 1, 1000 ],
			'All blocks have expired' => [ '20250405060708', 0, 0, 1000 ],
			'All blocks have expired, but UpdateRowsPerQuery is 1' => [ '20260505060708', 1, 0, 1 ],
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
			'20260505060708',
			$centralDbInReadOnlyMode ? 2 : 0,
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
