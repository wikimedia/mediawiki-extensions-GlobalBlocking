<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use MediaWiki\Block\Block;
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

	private function getGlobalBlockObject( stdClass $row, bool $xffValue = false ): GlobalBlock {
		return GlobalBlock::newFromRow( $row, $xffValue );
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
			'testingabc>' . self::$testPerformer->getName(),
			$globalBlock->getBlocker()->getName(),
			'::getBlocker did not return the expected UserIdentity'
		);
		$this->assertSame(
			0,
			$globalBlock->getBlocker()->getId(),
			'::getBlocker did not return the expected UserIdentity'
		);
	}

	public function testGetBlockerWhenBlockerDoesNotExistCentrally() {
		$this->setService( 'CentralIdLookup', function () {
			// Mock that the CentralIdLookup cannot find the blocking user locally or globally.
			$mock = $this->createMock( CentralIdLookup::class );
			$mock->method( 'localUserFromCentralId' )->willReturn( null );
			$mock->method( 'nameFromCentralId' )->willReturn( null );
			return $mock;
		} );
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', self::$globallyBlockedUser );
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertNull( $globalBlock->getBlocker(), '::getBlocker should return null if no user was found.' );
		$this->assertSame(
			'', $globalBlock->getByName(), '::getByName should return an empty string if no user was found.'
		);
		$this->assertSame( 0, $globalBlock->getBy(), '::getBy should return 0 if no user was found.' );
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
		$globalBlock = $this->getGlobalBlockObject( $row, $xffOption );
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
			'read' => [ 'read', false ],
		];
	}

	/** @dataProvider provideCreateAccountBlocked */
	public function testAppliesToRightForAccountCreation( $accountCreationDisabled ) {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', null );
		// Override the value of gb_create_account for the test, as ::applyToPasswordReset should be the value of
		// gb_create_account.
		$row->gb_create_account = (int)$accountCreationDisabled;
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame( $accountCreationDisabled, $globalBlock->appliesToRight( 'createaccount' ) );
		$this->assertSame( $accountCreationDisabled, $globalBlock->appliesToRight( 'autocreateaccount' ) );
	}

	/** @dataProvider provideCreateAccountBlocked */
	public function testAppliesToPasswordReset( $accountCreationDisabled ) {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', null );
		// Override the value of gb_create_account for the test, as ::applyToPasswordReset should be the value of
		// gb_create_account.
		$row->gb_create_account = (int)$accountCreationDisabled;
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame( $accountCreationDisabled, $globalBlock->appliesToPasswordReset() );
	}

	/** @dataProvider provideCreateAccountBlocked */
	public function testIsCreateAccountBlocked( $accountCreationDisabled ) {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', null );
		// Override the value of gb_create_account for the test
		$row->gb_create_account = (int)$accountCreationDisabled;
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame( $accountCreationDisabled, $globalBlock->isCreateAccountBlocked() );
	}

	public static function provideCreateAccountBlocked() {
		return [
			'Account creation is disabled' => [ true ],
			'Account creation is not disabled' => [ false ],
		];
	}

	/** @dataProvider provideIsAutoblocking */
	public function testIsAutoblocking( $isAutoblocking ) {
		$row = $this->getGlobalBlockRowForTarget( '6.7.8.9', self::$globallyBlockedUser );
		// Override the value of gb_enable_autoblock for the test
		$row->gb_enable_autoblock = (int)$isAutoblocking;
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame( $isAutoblocking, $globalBlock->isAutoblocking() );
	}

	public static function provideIsAutoblocking() {
		return [
			'Block is autoblocking' => [ true ],
			'Block not is autoblocking' => [ false ],
		];
	}

	/** @dataProvider provideGetType */
	public function testGetType( $isAutoBlock ) {
		$row = $this->getGlobalBlockRowForTarget( '1.2.3.4', null );
		// Override the value of gb_autoblock_parent_id for the test
		$row->gb_autoblock_parent_id = (int)$isAutoBlock;
		$globalBlock = $this->getGlobalBlockObject( $row );
		$this->assertSame( $isAutoBlock ? Block::TYPE_AUTO : Block::TYPE_IP, $globalBlock->getType() );
	}

	public static function provideGetType() {
		return [
			'Block is autoblocking' => [ true ],
			'Block not is autoblocking' => [ false ],
		];
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
