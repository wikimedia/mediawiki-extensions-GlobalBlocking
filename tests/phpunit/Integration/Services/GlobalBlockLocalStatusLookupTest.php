<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use InvalidArgumentException;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWikiIntegrationTestCase;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup
 * @group Database
 */
class GlobalBlockLocalStatusLookupTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		ConvertibleTimestamp::setFakeTime( '20230405030201' );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
	}

	public function testGetLocalWhitelistInfoThrowsExceptionOnInvalidArguments() {
		// Call the method under test and verify that an exception is thrown when null is provided
		// for both arguments.
		$this->expectException( InvalidArgumentException::class );
		GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockLocalStatusLookup()
			// The default arguments are null (which throw the exception).
			->getLocalWhitelistInfo();
	}

	/** @dataProvider provideGetLocalWhitelistInfo */
	public function testGetLocalWhitelistInfo( $id, $target, $expectedResult ) {
		// Tests ::getLocalWhitelistInfo for a variety of arguments. If updating the
		// data provider, make sure to update the ::addDBDataOnce method as well.
		$this->assertSame(
			$expectedResult,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLocalStatusLookup()
				->getLocalWhitelistInfo( $id, $target ),
			'::getLocalWhitelistInfo did not return the expected result.'
		);
	}

	public static function provideGetLocalWhitelistInfo() {
		return [
			'ID provided, target is null' => [
				// The $id argument to the method under test
				1234,
				// The $target argument to the method under test
				null,
				// The expected result of the method under test
				[ 'user' => 123, 'reason' => 'Test reason' ],
			],
			'No ID provided, but IP target is provided' => [
				null, '127.0.0.1', [ 'user' => 123, 'reason' => 'Test reason' ],
			],
			'No ID provided, IP target is provided but no row found' => [ null, '127.0.0.2', false ],
			'ID provided, target is null but no row found' => [ 12345, null, false ],
			'ID provided and IP target provided but no row found' => [ 12345, '1.2.3.4', false ],
			'IP range target provided with no row found' => [ null, '1.2.3.4/24', false ],
			'Non-existent user target provided' => [ null, 'Test-non-existent-user', false ],
		];
	}

	public function testGetLocalWhitelistInfoForUser() {
		$testUserName = $this->getDb()->newSelectQueryBuilder()
			->select( 'gbw_address' )
			->from( 'global_block_whitelist' )
			->where( [ 'gbw_by' => 123 ] )
			->fetchField();
		$this->testGetLocalWhitelistInfo( null, $testUserName, [ 'user' => 123, 'reason' => 'Test reason2' ] );
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
		$testTarget = $this->getTestUser()->getUser();
		// The tests should not modify the database, so we don't need to reset the tables
		// between tests in this class.
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'global_block_whitelist' )
			->rows( [
				[
					'gbw_by' => 123,
					'gbw_by_text' => 'Test user',
					'gbw_reason' => 'Test reason',
					'gbw_address' => '127.0.0.1',
					'gbw_target_central_id' => 0,
					'gbw_expiry' => $this->getDb()->getInfinity(),
					'gbw_id' => 1234,
				],
				[
					'gbw_by' => 123,
					'gbw_by_text' => 'Test user',
					'gbw_reason' => 'Test reason2',
					'gbw_address' => $testTarget->getName(),
					'gbw_target_central_id' => $this->getServiceContainer()
						->getCentralIdLookup()->centralIdFromName( $testTarget->getName() ),
					'gbw_expiry' => $this->getDb()->timestamp( '20240405030201' ),
					'gbw_id' => 123,
				],
			] )
			->execute();
	}
}
