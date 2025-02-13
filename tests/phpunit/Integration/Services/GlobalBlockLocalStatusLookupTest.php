<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

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

	/** @dataProvider provideGetLocalStatusInfo */
	public function testGetLocalStatusInfo( $id, $expectedResult ) {
		$this->assertSame(
			$expectedResult,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLocalStatusLookup()
				->getLocalStatusInfo( $id ),
			'::getLocalStatusInfo did not return the expected result.'
		);
	}

	public static function provideGetLocalStatusInfo() {
		return [
			'Provided global block ID is a locally disabled global block on an IP' => [
				// The $id argument to the method under test.
				1234,
				// The expected result of the method under test
				[ 'user' => 123, 'reason' => 'Test reason' ],
			],
			'Provided global block ID is a locally disabled global block on a user' => [
				123, [ 'user' => 123, 'reason' => 'Test reason2' ],
			],
			'Provided global block ID exists but is not locally disabled' => [ 12345, false ],
			'No such global block ID' => [ 3458484, false ],
		];
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
