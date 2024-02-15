<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\WikiMap\WikiMap;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Wikimedia\IPUtils;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup
 * @group Database
 */
class GlobalBlockLookupTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		// Set a fake time such that the expiry of all blocks is after this date (otherwise the lookup may
		// not return the expired blocks and cause failures).
		ConvertibleTimestamp::setFakeTime( '20240219050403' );
	}

	public function testGetUserBlockErrors() {
		$globalBlockLookup = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockLookup();
		$testUser = $this->getTestUser()->getUser();
		$this->assertNotCount(
			0,
			$globalBlockLookup->getUserBlockErrors( $testUser, '77.8.9.10' ),
			'::getUserBlockErrors should have returned an array with at least one error.'
		);
	}

	/** @dataProvider provideGetUserBlockForNamedWhenXFFHeaderIsNotBlocked */
	public function testGetUserBlockForNamedWhenXFFHeaderIsNotBlocked( $xffHeader ) {
		$this->setMwGlobals( 'wgGlobalBlockingBlockXFF', true );
		$testUser = $this->getTestUser()->getUser();
		RequestContext::getMain()->setUser( $testUser );
		RequestContext::getMain()->getRequest()->setHeader( 'X-Forwarded-For', $xffHeader );
		$this->testGetUserBlockOnNoBlock(
			$testUser, null,
			'No matching global block row should have been found using the XFF header by ::getUserBlock.'
		);
	}

	public static function provideGetUserBlockForNamedWhenXFFHeaderIsNotBlocked() {
		return [
			'XFF header has only spaces' => [ '   ' ],
			'XFF header is invalid' => [ 'abdef' ],
			'XFF header is for an IP which is not blocked' => [ '1.2.3.4' ],
		];
	}

	/** @dataProvider provideGetUserBlockForNamedWhenXffBlocked */
	public function testGetUserBlockForNamedWhenXffBlocked( $xffHeader, $expectedGlobalBlockId ) {
		$this->setMwGlobals( 'wgGlobalBlockingBlockXFF', true );
		$testUser = $this->getTestUser()->getUser();
		RequestContext::getMain()->setUser( $testUser );
		RequestContext::getMain()->getRequest()->setHeader( 'X-Forwarded-For', $xffHeader );
		$actualGlobalBlockObject = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockLookup()
			->getUserBlock( $testUser, '1.2.3.4' );
		$this->assertNotNull(
			$actualGlobalBlockObject,
			'A matching global block row should have been found by ::getUserBlock.'
		);
		$this->assertSame(
			$expectedGlobalBlockId,
			$actualGlobalBlockObject->getId(),
			'The GlobalBlock object returned was not for the expected row.'
		);
	}

	public static function provideGetUserBlockForNamedWhenXffBlocked() {
		return [
			'One XFF header IP is blocked' => [ '1.2.3.5, 77.8.9.10', 3 ],
			'Two XFF header IPs are blocked but the first is locally disabled' => [ '127.0.0.1, 77.8.9.10', 3 ],
		];
	}

	/** @dataProvider provideGetUserBlock */
	public function testGetUserBlockForNamedUser( $ip, $expectedGlobalBlockId ) {
		$actualGlobalBlockObject = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockLookup()
			->getUserBlock( $this->getTestUser()->getUser(), $ip );
		$this->assertNotNull(
			$actualGlobalBlockObject,
			'A matching global block row should have been found by ::getUserBlock.'
		);
		$this->assertSame(
			$expectedGlobalBlockId,
			$actualGlobalBlockObject->getId(),
			'The GlobalBlock object returned was not for the expected row.'
		);
		// Assert that the GlobalBlock returned has the correct properties for a selected fields.
		$rowFromTheDb = (array)$this->getDb()->newSelectQueryBuilder()
			->select( [ 'gb_by_central_id', 'gb_address', 'gb_reason', 'gb_timestamp' ] )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $expectedGlobalBlockId ] )
			->fetchRow();
		$this->assertSame(
			$this->getServiceContainer()
				->getCentralIdLookup()
				->nameFromCentralId( $rowFromTheDb['gb_by_central_id'] ),
			$actualGlobalBlockObject->getByName(),
			'The GlobalBlock object returned by ::getUserBlock does not have the expected blocker name.'
		);
		$this->assertSame(
			$rowFromTheDb['gb_address'],
			$actualGlobalBlockObject->getTargetName(),
			'The GlobalBlock object returned by ::getUserBlock does not have the expected target.'
		);
		$this->assertSame(
			$rowFromTheDb['gb_timestamp'],
			$actualGlobalBlockObject->getTimestamp(),
			'The GlobalBlock object returned by ::getUserBlock does not have the expected timestamp.'
		);
	}

	public static function provideGetUserBlock() {
		return [
			'The IP used by the named user is blocked' => [ '77.8.9.10', 3 ],
			'The IP range used by the named user is blocked' => [ '88.8.9.5', 4 ],
		];
	}

	public function testGetUserBlockGlobalBlockingAllowedRanges() {
		$this->setMwGlobals( 'wgGlobalBlockingAllowedRanges', [ '1.2.3.4/30', '5.6.7.8/24' ] );
		$this->testGetUserBlockOnNoBlock(
			UserIdentityValue::newAnonymous( '5.6.7.8' ), null,
			'No matching global block row should have been found by ::getUserBlock because the IP is in ' .
			'a range that is exempt from global blocking.'
		);
	}

	/** @dataProvider provideExemptRights */
	public function testGetUserBlockWhenUserIsExempt( $exemptRight ) {
		$userMock = $this->createMock( User::class );
		$userMock->method( 'isAllowed' )
			->willReturnCallback( static function ( $right ) use ( $exemptRight ) {
				return $right === $exemptRight;
			} );
		$userMock->method( 'getName' )
			->willReturn( 'TestUser-' . $exemptRight );
		$globalBlockLookup = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockLookup();
		$this->assertNull(
			$globalBlockLookup->getUserBlock( $userMock, '127.0.0.1' ),
			'A user exempt from the global block should not be blocked.'
		);
	}

	public static function provideExemptRights() {
		return [
			'ipblock-exempt' => [ 'ipblock-exempt' ],
			'globalblock-exempt' => [ 'globalblock-exempt' ],
		];
	}

	/** @dataProvider provideGetUserBlockOnNoBlock */
	public function testGetUserBlockOnNoBlock( $userIdentity, $ip, $message = null ) {
		$this->assertNull(
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLookup()
				->getUserBlock(
					$this->getServiceContainer()->getUserFactory()->newFromUserIdentity( $userIdentity ),
					$ip
				),
			$message ?? 'No matching global block row should have been found by ::getUserBlock.'
		);
	}

	public static function provideGetUserBlockOnNoBlock() {
		return [
			'No block on anonymous user with IP as null' => [ UserIdentityValue::newAnonymous( '1.2.3.4' ), null ],
			'No block on test user with IP as null' => [ UserIdentityValue::newRegistered( 1, 'TestUser' ), null ],
			'No block on test user with IP' => [ UserIdentityValue::newRegistered( 1, 'TestUser' ), '1.2.3.4' ],
			'Block is locally disabled' => [
				UserIdentityValue::newAnonymous( '127.0.0.2' ),
				'127.0.0.2',
				'The matching global block has been locally disabled, so should not be returned by ::getUserBlock.'
			],
		];
	}

	/** @dataProvider provideGetGlobalBlockingBlockWhenNoRowsFound */
	public function testGetGlobalBlockingBlockWhenNoRowsFound( $ip, $anon ) {
		$this->assertNull(
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLookup()
				->getGlobalBlockingBlock( $ip, $anon ),
			'No matching global block row should have been found by ::getGlobalBlockingBlock.'
		);
	}

	public static function provideGetGlobalBlockingBlockWhenNoRowsFound() {
		return [
			'No global block on the given single IP target' => [
				// The $ip argument for provided to ::getGlobalBlockingBlock
				'1.2.3.4',
				// The $anon argument for provided to ::getGlobalBlockingBlock
				false,
			],
			'No global block on the given range' => [ '1.2.3.4/20', false ],
		];
	}

	/** @dataProvider provideGetGlobalBlockingBlock */
	public function testGetGlobalBlockingBlock( $ip, $anon, $expectedRowId ) {
		$expectedRow = (array)$this->getDb()->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $expectedRowId ] )
			->fetchRow();
		$this->assertArrayEquals(
			$expectedRow,
			(array)GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLookup()
				->getGlobalBlockingBlock( $ip, $anon ),
			false,
			true,
			'The global block row returned by ::getGlobalBlockingBlock is not as expected.'
		);
	}

	public static function provideGetGlobalBlockingBlock() {
		return [
			'Single IP target is subject to two blocks, but $anon is false which excludes the most specific one' => [
				// The target IP or IP range provided as the $ip argument to ::getGlobalBlockingBlock
				'127.0.0.1',
				// The $anon argument provided to ::getGlobalBlockingBlock
				false,
				// The ID of the global block row from the globalblocks table that should be returned by
				// ::getGlobalBlockingBlock.
				2,
			],
			'Single IP target is subject to two blocks' => [ '127.0.0.1', true, 1 ],
			'Single IP target is subject to a range block' => [ '127.0.0.2', true, 2 ],
			'Range target is subject to a range block' => [ '127.0.0.0/27', true, 2 ],
		];
	}

	/** @dataProvider provideGetGlobalBlockId */
	public function testGetGlobalBlockId( $ip, $queryFlags, $expectedResult ) {
		$this->assertSame(
			$expectedResult,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockLookup()
				->getGlobalBlockId( $ip, $queryFlags ),
			'The global block ID returned by the method under test is not as expected.'
		);
	}

	public static function provideGetGlobalBlockId() {
		return [
			'No global block on given target' => [ '1.2.3.4', DB_REPLICA, 0 ],
			'Global block on given target while reading from primary' => [ '127.0.0.1', DB_PRIMARY, 1 ],
		];
	}

	public function addDBDataOnce() {
		// We can add the DB data once for this class as the service should not modify, insert or delete rows from
		// the database.
		$testUser = $this->getTestSysop()->getUserIdentity();
		// Insert a range block and single IP block for the test.
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'globalblocks' )
			->rows( [
				[
					'gb_id' => 1,
					'gb_address' => '127.0.0.1',
					'gb_by' => $testUser->getName(),
					'gb_by_central_id' => $this->getServiceContainer()
						->getCentralIdLookup()
						->centralIdFromLocalUser( $testUser ),
					'gb_by_wiki' => WikiMap::getCurrentWikiId(),
					'gb_reason' => 'test',
					'gb_timestamp' => $this->getDb()->timestamp( '20230405060708' ),
					'gb_anon_only' => 1,
					'gb_expiry' => $this->getDb()->encodeExpiry( '20240405060708' ),
					'gb_range_start' => IPUtils::toHex( '127.0.0.1' ),
					'gb_range_end' => IPUtils::toHex( '127.0.0.1' ),
				],
				[
					'gb_id' => 2,
					'gb_address' => '127.0.0.0/24',
					'gb_by' => $testUser->getName(),
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
				],
				[
					'gb_id' => 3,
					'gb_address' => '77.8.9.10',
					'gb_by' => $testUser->getName(),
					'gb_by_central_id' => $this->getServiceContainer()
						->getCentralIdLookup()
						->centralIdFromLocalUser( $testUser ),
					'gb_by_wiki' => WikiMap::getCurrentWikiId(),
					'gb_reason' => 'test',
					'gb_timestamp' => $this->getDb()->timestamp( '20080405060708' ),
					'gb_anon_only' => 0,
					'gb_expiry' => $this->getDb()->encodeExpiry( '20240405060708' ),
					'gb_range_start' => IPUtils::toHex( '77.8.9.10' ),
					'gb_range_end' => IPUtils::toHex( '77.8.9.10' ),
				],
				[
					'gb_id' => 4,
					'gb_address' => '88.8.9.0/24',
					'gb_by' => $testUser->getName(),
					'gb_by_central_id' => $this->getServiceContainer()
						->getCentralIdLookup()
						->centralIdFromLocalUser( $testUser ),
					'gb_by_wiki' => WikiMap::getCurrentWikiId(),
					'gb_reason' => 'test',
					'gb_timestamp' => $this->getDb()->timestamp( '20080405060708' ),
					'gb_anon_only' => 0,
					'gb_expiry' => $this->getDb()->encodeExpiry( '20240405060708' ),
					'gb_range_start' => IPUtils::toHex( '88.8.9.0' ),
					'gb_range_end' => IPUtils::toHex( '88.8.9.255' ),
				],
			] )
			->execute();
		// Insert a whitelist entry for the range block
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'global_block_whitelist' )
			->rows( [
				[
					'gbw_by' => $testUser->getId(),
					'gbw_by_text' => $testUser->getName(),
					'gbw_reason' => 'test-override',
					'gbw_address' => '127.0.0.0/24',
					'gbw_expiry' => $this->getDb()->encodeExpiry( '20250405060708' ),
					'gbw_id' => 2,
				],
			] )
			->execute();
	}
}
