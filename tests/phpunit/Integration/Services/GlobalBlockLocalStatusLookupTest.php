<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use InvalidArgumentException;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup
 * @group Database
 */
class GlobalBlockLocalStatusLookupTest extends MediaWikiIntegrationTestCase {
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
	public function testGetLocalWhitelistInfo( $id, $address, $expectedResult ) {
		// Tests ::getLocalWhitelistInfo for a variety of arguments. If updating the
		// data provider, make sure to update the ::addDBDataOnce method as well.
		$this->assertSame(
			$expectedResult,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLocalStatusLookup()
				->getLocalWhitelistInfo( $id, $address ),
			'::getLocalWhitelistInfo did not return the expected result.'
		);
	}

	public static function provideGetLocalWhitelistInfo() {
		return [
			'ID provided, address is null' => [
				// The $id argument to the method under test
				1234,
				// The $address argument to the method under test
				null,
				// The expected result of the method under test
				[ 'user' => 123, 'reason' => 'Test reason' ],
			],
			'No ID provided, but address is provided' => [
				null, '127.0.0.1', [ 'user' => 123, 'reason' => 'Test reason' ],
			],
			'No ID provided, address is provided but no row found' => [
				null, '127.0.0.2', false,
			],
			'ID provided, address is null but no row found' => [
				12345, null, false,
			],
			'ID provided and address provided but no row found' => [
				12345, '1.2.3.4', false,
			],
		];
	}

	public function addDBDataOnce() {
		// The tests should not modify the database, so we don't need to reset the tables
		// between tests in this class.
		// TODO: Create a service to manage the local block status which this
		//  could call instead of adding the data manually?
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'global_block_whitelist' )
			->row( [
				'gbw_by' => 123,
				'gbw_by_text' => 'Test user',
				'gbw_reason' => 'Test reason',
				'gbw_address' => '127.0.0.1',
				'gbw_expiry' => $this->getDb()->getInfinity(),
				'gbw_id' => 1234,
			] )
			->execute();
	}
}
