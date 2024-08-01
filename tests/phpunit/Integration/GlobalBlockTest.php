<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\ExternalUserNames;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use stdClass;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlock
 * @group Database
 */
class GlobalBlockTest extends MediaWikiIntegrationTestCase {

	private static User $testPerformer;
	private static User $globallyBlockedUser;

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	private function getGlobalBlockRowForTarget( string $ip, ?User $user ): stdClass {
		$centralId = 0;
		if ( $user !== null ) {
			$centralId = $this->getServiceContainer()->getCentralIdLookup()->centralIdFromLocalUser( $user );
		}
		$row = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLookup()
			->getGlobalBlockingBlock( $ip, $centralId );
		if ( $row === null ) {
			$this->fail( 'Unable to find relevant global block row for use in tests' );
		}
		return $row;
	}

	private function getGlobalBlockObject( stdClass $row, array $overriddenOptions = [] ) {
		return new GlobalBlock(
			$row,
			array_merge(
				[
					'address' => $row->gb_address, 'reason' => $row->gb_reason, 'timestamp' => $row->gb_timestamp,
					'anonOnly' => $row->gb_anon_only, 'expiry' => $row->gb_expiry, 'xff' => false,
				],
				$overriddenOptions
			)
		);
	}

	public function testGetBy() {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', self::$globallyBlockedUser );
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame(
			self::$testPerformer->getId(), $globalBlock->getBy(), '::getBy did not return the expected ID'
		);
	}

	public function testGetByName() {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', self::$globallyBlockedUser );
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame(
			self::$testPerformer->getName(), $globalBlock->getByName(),
			'::getByName did not return the correct username'
		);
	}

	public function testGetBlocker() {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', self::$globallyBlockedUser );
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertTrue(
			self::$testPerformer->equals( $globalBlock->getBlocker() ),
			'::getBlocker did not return the expected UserIdentity'
		);
	}

	public function testGetBlockerWhenBlockerIsAttachedLocally() {
		$this->setService( 'CentralIdLookup', function () {
			// Mock that the CentralIdLookup finds the blocking user locally, and that it is attached on all wikis.
			$mock = $this->createMock( CentralIdLookup::class );
			$mock->method( 'localUserFromCentralId' )->willReturn( self::$testPerformer );
			$mock->method( 'isAttached' )->willReturn( true );
			return $mock;
		} );
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', self::$globallyBlockedUser );
		// Modify gb_by_wiki to something other than the current wiki
		$row->gb_by_wiki = 'testingabc';
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertTrue(
			self::$testPerformer->equals( $globalBlock->getBlocker() ),
			'::getBlocker did not return the expected UserIdentity'
		);
	}

	public function testGetBlockerWhenBlockerDoesNotExistLocally() {
		$this->setService( 'CentralIdLookup', function () {
			// Mock that the CentralIdLookup cannot find the blocking user locally, but is able to find the username
			// for the central ID.
			$mock = $this->createMock( CentralIdLookup::class );
			$mock->method( 'localUserFromCentralId' )->willReturn( null );
			$mock->method( 'nameFromCentralId' )->willReturn( self::$testPerformer->getName() );
			return $mock;
		} );
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', self::$globallyBlockedUser );
		// Modify gb_by_wiki to a fixed value to allow assertions below.
		$row->gb_by_wiki = 'testingabc';
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertTrue(
			ExternalUserNames::isExternal( $globalBlock->getBlocker()->getName() ),
			'::getBlocker did not return an external username as expected'
		);
		$this->assertSame(
			'Testingabc>' . self::$testPerformer->getName(),
			$globalBlock->getBlocker()->getName(),
			'::getBlocker did not return the expected UserIdentity'
		);
		$this->assertSame(
			0,
			$globalBlock->getBlocker()->getId(),
			'::getBlocker did not return the expected UserIdentity'
		);
	}

	public function testGetId() {
		// Get the IP block for this test, to ensure that we don't always use the same GlobalBlock instance.
		$globalBlock = $this->getGlobalBlockObject( $this->getGlobalBlockRowForTarget( '1.2.3.4', null ) );
		$this->assertSame( 1, $globalBlock->getId(), '::getId did not return the expected gb_id' );
	}

	public function testGetIdentifier() {
		$globalBlock = $this->getGlobalBlockObject( $this->getGlobalBlockRowForTarget( '1.2.3.4', null ) );
		$this->assertSame(
			1, $globalBlock->getIdentifier(), '::getIdentifier did not return the expected gb_id'
		);
	}

	/** @dataProvider provideIsXffBlock */
	public function testGetXff( bool $xffOption ) {
		// Get the block on 1.2.3.4, but mock that this block matched because of the XFF header.
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', null );
		$globalBlock = $this->getGlobalBlockObject( $row, [ 'xff' => $xffOption ] );
		$this->assertSame( $xffOption, $globalBlock->getXff(), '::getXff did not return the expected value' );
	}

	public static function provideIsXffBlock() {
		return [
			'XFF block' => [ true ],
			'Non-XFF block' => [ false ],
		];
	}

	/** @dataProvider provideAppliesToRight */
	public function testAppliesToRight( $right, $expected ) {
		$globalBlock = $this->getGlobalBlockObject( $this->getGlobalBlockRowForTarget( '1.2.3.4', null ) );
		$this->assertSame( $expected, $globalBlock->appliesToRight( $right ) );
	}

	public static function provideAppliesToRight() {
		return [
			'upload' => [ 'upload', true ],
			'createaccount' => [ 'createaccount', true ],
			'autocreateaccount' => [ 'autocreateaccount', true ],
			'read' => [ 'read', false ],
		];
	}

	public function testAppliesToPasswordReset() {
		$globalBlock = $this->getGlobalBlockObject( $this->getGlobalBlockRowForTarget( '1.2.3.4', null ) );
		$this->assertTrue( $globalBlock->appliesToPasswordReset() );
	}

	public function testIsCreateAccountBlocked() {
		$globalBlock = $this->getGlobalBlockObject( $this->getGlobalBlockRowForTarget( '1.2.3.4', null ) );
		$this->assertTrue( $globalBlock->isCreateAccountBlocked() );
	}

	public function addDBDataOnce() {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
		// Create some testing globalblocks database rows for IPs, IP ranges, and accounts for use in the above tests.
		// These should not be modified by any code in GlobalBlock, so we can insert it once before the first test
		// in this class.
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockManager = $globalBlockingServices->getGlobalBlockManager();
		$testPerformer = $this->getTestUser( [ 'steward', 'sysop' ] )->getUser();
		$this->assertStatusGood(
			$globalBlockManager->block( '1.2.3.4', 'Test reason1', 'infinity', $testPerformer )
		);
		$globallyBlockedUser = $this->getMutableTestUser()->getUser();
		$this->assertStatusGood(
			$globalBlockManager->block( $globallyBlockedUser->getName(), 'Test reason4', 'infinite', $testPerformer )
		);
		self::$testPerformer = $testPerformer;
		self::$globallyBlockedUser = $globallyBlockedUser;
	}
}
