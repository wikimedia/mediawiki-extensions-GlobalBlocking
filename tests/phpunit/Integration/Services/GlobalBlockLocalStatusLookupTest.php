<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use InvalidArgumentException;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;
use MediaWikiIntegrationTestCase;
use Wikimedia\IPUtils;
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
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
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
			->select( 'gb_address' )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => 123 ] )
			->fetchField();
		$this->testGetLocalWhitelistInfo( null, $testUserName, [ 'user' => 123, 'reason' => 'Test reason2' ] );
	}

	/** @dataProvider provideIsGlobalBlockLocallyDisabledForBlockApplication */
	public function testIsGlobalBlockLocallyDisabledForBlockApplication( $id, $expectedReturnValue ) {
		// Allow the MediaWiki message override for the local autoblocking exemption list to take effect.
		$this->getServiceContainer()->getMessageCache()->enable();
		$this->assertSame(
			$expectedReturnValue,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLocalStatusLookup()
				->isGlobalBlockLocallyDisabledForBlockApplication( $id )
		);
	}

	public static function provideIsGlobalBlockLocallyDisabledForBlockApplication() {
		return [
			'IP range which is not locally disabled' => [ 123456, false ],
			'Global autoblock which is locally disabled via local autoblocking exemption list' => [ 12345, true ],
		];
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
		$testTarget = $this->getTestUser()->getUser();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUser();
		// The tests should not modify the database, so we don't need to reset the tables
		// between tests in this class.
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'globalblocks' )
			->row( [
				'gb_address' => '127.0.0.1',
				'gb_target_central_id' => 0,
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testPerformer ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20230405060708' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->getInfinity(),
				'gb_range_start' => IPUtils::toHex( '127.0.0.1' ),
				'gb_range_end' => IPUtils::toHex( '127.0.0.1' ),
				'gb_autoblock_parent_id' => 0,
				'gb_id' => 1234,
			] )
			->row( [
				'gb_address' => $testTarget->getName(),
				'gb_target_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()->centralIdFromName( $testTarget->getName() ),
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testPerformer ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20230405060708' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->timestamp( '20240405030201' ),
				'gb_range_start' => '',
				'gb_range_end' => '',
				'gb_autoblock_parent_id' => 0,
				'gb_id' => 123,
			] )
			->row( [
				'gb_address' => '7.8.9.40',
				'gb_target_central_id' => 0,
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testPerformer ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20230405060709' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->getInfinity(),
				'gb_range_start' => IPUtils::toHex( '7.8.9.40' ),
				'gb_range_end' => IPUtils::toHex( '7.8.9.40' ),
				'gb_autoblock_parent_id' => 123,
				'gb_id' => 12345,
			] )
			->row( [
				'gb_address' => '1.2.3.0/24',
				'gb_target_central_id' => 0,
				'gb_by_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()
					->centralIdFromLocalUser( $testPerformer ),
				'gb_by_wiki' => WikiMap::getCurrentWikiId(),
				'gb_reason' => 'test',
				'gb_timestamp' => $this->getDb()->timestamp( '20230405060710' ),
				'gb_anon_only' => 0,
				'gb_expiry' => $this->getDb()->timestamp( '20230506060710' ),
				'gb_range_start' => IPUtils::toHex( '1.2.3.0' ),
				'gb_range_end' => IPUtils::toHex( '1.2.3.255' ),
				'gb_autoblock_parent_id' => 0,
				'gb_id' => 123456,
			] )
			->caller( __METHOD__ )
			->execute();
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'global_block_whitelist' )
			->row( [
				'gbw_by' => 123,
				'gbw_by_text' => 'Test user',
				'gbw_reason' => 'Test reason',
				'gbw_expiry' => $this->getDb()->getInfinity(),
				'gbw_id' => 1234,
			] )
			->row( [
				'gbw_by' => 123,
				'gbw_by_text' => 'Test user',
				'gbw_reason' => 'Test reason2',
				'gbw_expiry' => $this->getDb()->timestamp( '20240405030201' ),
				'gbw_id' => 123,
			] )
			->caller( __METHOD__ )
			->execute();
		// Define the local autoblock exemption list for the tests.
		$this->editPage(
			Title::newFromText( 'block-autoblock-exemptionlist', NS_MEDIAWIKI ),
			'[[Test]]. This is a autoblocking exemption list description.' .
			"\n\n* 7.8.9.0/24"
		);
	}
}
