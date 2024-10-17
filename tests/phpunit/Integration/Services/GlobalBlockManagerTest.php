<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Language\RawMessage;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\WikiMap\WikiMap;
use MediaWikiIntegrationTestCase;
use Wikimedia\IPUtils;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager
 * @group Database
 */
class GlobalBlockManagerTest extends MediaWikiIntegrationTestCase {
	use MockAuthorityTrait;

	public function setUp(): void {
		ConvertibleTimestamp::setFakeTime( '2021-03-02T22:00:00Z' );
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
		// By default, set the feature config flag for autoblocks to be true. Tests which test what happens when this
		// is disabled will set the config value to false.
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', true );
	}

	/**
	 * @param string $target
	 * @return array
	 */
	private function getGlobalBlock( string $target ) {
		$blockOptions = [];
		$dbr = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingConnectionProvider()
			->getReplicaGlobalBlockingDatabase();
		$queryBuilder = $dbr->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( $dbr->expr( 'gb_expiry', '>', $dbr->timestamp() ) )
			->caller( __METHOD__ );
		if ( GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			$queryBuilder->andWhere( [ 'gb_id' => GlobalBlockLookup::isAGlobalBlockId( $target ) ] );
		} elseif ( !IPUtils::isIPAddress( $target ) ) {
			$queryBuilder->andWhere( [
				'gb_address' => $target,
				// Used to assert that the central ID column is set correctly.
				'gb_target_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()->centralIdFromName( $target )
			] );
		} else {
			$queryBuilder->andWhere( [ 'gb_address' => $target ] );
		}
		$block = $queryBuilder->fetchRow();
		if ( $block ) {
			$blockOptions['anon-only'] = $block->gb_anon_only;
			$blockOptions['allow-account-creation'] = (string)intval( !$block->gb_create_account );
			$blockOptions['enable-autoblock'] = $block->gb_enable_autoblock;
			$blockOptions['reason'] = $block->gb_reason;
			$blockOptions['expiry'] = ( $block->gb_expiry === 'infinity' )
				? 'infinity'
				: wfTimestamp( TS_ISO_8601, $block->gb_expiry );
			$blockOptions['timestamp'] = wfTimestamp( TS_ISO_8601, $block->gb_timestamp );
			$blockOptions['blocker'] = [ 'byCentralId' => $block->gb_by_central_id, 'byWiki' => $block->gb_by_wiki ];
			$blockOptions['parentBlockId'] = $block->gb_autoblock_parent_id;
		}

		return $blockOptions;
	}

	private function getTargetForLogEntry( string $target ): LinkTarget {
		// We need to use TitleValue::tryNew for block IDs, as the block ID contains a "#" character which
		// causes the title to be rejected by Title::makeTitleSafe. In all other cases we can use Title::makeTitleSafe.
		if ( GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			return TitleValue::tryNew( NS_USER, $target );
		} else {
			return Title::makeTitleSafe( NS_USER, $target );
		}
	}

	private function assertThatLogWasAdded( $target, $action, $failMessage ) {
		$this->assertSame(
			1,
			(int)$this->getDb()->newSelectQueryBuilder()
				->select( 'COUNT(*)' )
				->from( 'logging' )
				->where( [
					'log_type' => 'gblblock',
					'log_action' => $action,
					'log_namespace' => NS_USER,
					'log_title' => $this->getTargetForLogEntry( $target )->getDBkey(),
				] )
				->caller( __METHOD__ )
				->fetchField(),
			$failMessage
		);
	}

	public function testBlockOnRaceCondition() {
		// Mock GlobalBlockLookup::getGlobalBlockId to return no block ID even if one exists.
		// This simulates a race condition, on attempting to block an already-blocked target.
		$globalBlockLookup = $this->createMock( GlobalBlockLookup::class );
		$globalBlockLookup->method( 'getGlobalBlockId' )
			->willReturn( 0 );
		// Make the mock GlobalBlockLookup the service instance for the test
		$this->setService( 'GlobalBlocking.GlobalBlockLookup', $globalBlockLookup );
		// Call ::block to create the first block. The second call will test the race condition handling.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$globalBlockManager->block(
			'1.2.3.4',
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser()
		);
		// Call ::block again with the same arguments to test the race condition handling.
		$errors = $globalBlockManager->block(
			'1.2.3.4',
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser()
		);
		$this->assertStatusError( 'globalblocking-block-failure', $errors );
	}

	private function assertGlobalBlockOptionApplied(
		array $data, string $optionKey, $expectedValueWhenPresent, $expectedValueWhenNotPresent, array $actual
	) {
		if ( in_array( $optionKey, $data['options'] ) ) {
			$expectedAnon = $expectedValueWhenPresent;
		} else {
			$expectedAnon = $expectedValueWhenNotPresent;
		}
		$this->assertSame( $expectedAnon, $actual[ $optionKey ] );
	}

	/** @dataProvider provideBlock */
	public function testBlock( array $data, string $expectedError ) {
		// Create a testing block on 1.2.3.6 so that we can test block modification.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$globalBlockManager->block(
			'1.2.3.6',
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			[ 'anon-only' ]
		);

		$errors = $globalBlockManager->block(
			$data[ 'target' ],
			$data[ 'reason' ],
			$data[ 'expiry' ],
			$this->getMutableTestUser( 'steward' )->getUser(),
			$data[ 'options' ]
		);
		if ( $expectedError !== '' ) {
			$this->assertStatusMessage( $expectedError, $errors );
		} else {
			$actual = $this->getGlobalBlock( $data[ 'target' ] );
			$this->assertSame( $data[ 'reason' ], $actual[ 'reason' ] );
			$this->assertSame( $data[ 'expiry' ], $actual[ 'expiry' ] );
			$this->assertGlobalBlockOptionApplied( $data, 'anon-only', '1', '0', $actual );
			$this->assertGlobalBlockOptionApplied( $data, 'allow-account-creation', '1', '0', $actual );
			$this->assertGlobalBlockOptionApplied( $data, 'enable-autoblock', '1', '0', $actual );
			// Assert that a log entry was added to the 'logging' table for the block
			$this->assertThatLogWasAdded(
				$data[ 'target' ],
				in_array( 'modify', $data['options'] ) ? 'modify' : 'gblock',
				'A logging entry for the global block was not found in the logging table.'
			);
		}
	}

	public static function provideBlock() {
		return [
			'good' => [
				'data' => [
					'target' => '1.2.3.4',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => '',
			],
			'good with account creation enabled' => [
				'data' => [
					'target' => '1.2.3.4',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only', 'allow-account-creation' ],
				],
				'expectedError' => '',
			],
			'good range' => [
				'data' => [
					'target' => '1.2.3.0/24',
					'reason' => 'Test block1234',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => '',
			],
			'good modify' => [
				'data' => [
					'target' => '1.2.3.6',
					'reason' => 'Test block1',
					'expiry' => '2021-03-06T23:00:00Z',
					'options' => [ 'anon-only', 'modify' ],
				],
				'expectedError' => '',
			],
			'good modify with account creation enabled' => [
				'data' => [
					'target' => '1.2.3.6',
					'reason' => 'Test block1',
					'expiry' => '2021-03-06T23:00:00Z',
					'options' => [ 'anon-only', 'modify', 'allow-account-creation' ],
				],
				'expectedError' => '',
			],
			'Invalid username' => [
				'data' => [
					'target' => 'Template:Test User#test',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [],
				],
				'expectedError' => 'globalblocking-block-target-invalid',
			],
			'no such user target' => [
				'data' => [
					'target' => 'Nonexistent User',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-target-invalid',
			],
			'no such global block ID' => [
				'data' => [
					'target' => '#1234567',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only', 'modify' ],
				],
				'expectedError' => 'globalblocking-notblocked-id',
			],
			'bad expiry' => [
				'data' => [
					'target' => '1.2.3.5',
					'reason' => 'Test block',
					'expiry' => '2021-03-06T25:00:00Z',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-expiryinvalid',
			],
			'bad range' => [
				'data' => [
					'target' => '1.0.0.0/10',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-bigrange',
			],
			'no modify' => [
				'data' => [
					'target' => '1.2.3.6',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-alreadyblocked',
			],
			'Attempting to autoblock an IP address' => [
				'data' => [
					'target' => '1.2.3.6',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'enable-autoblock' ],
				],
				'expectedError' => 'globalblocking-block-enable-autoblock-on-ip',
			],
		];
	}

	/** @dataProvider provideBlockForExistingUser */
	public function testBlockForExistingUser( array $data, string $expectedError ) {
		$this->overrideConfigValue( 'GlobalBlockingMaximumIPsToRetroactivelyAutoblock', 2 );
		$testUser = $this->getTestUser()->getUser();
		// Define a GlobalBlockingGetRetroactiveAutoblockIPs hook handler which will always return 1.2.3.4, so that
		// we can test retroactive autoblocking.
		$this->setTemporaryHook(
			'GlobalBlockingGetRetroactiveAutoblockIPs',
			function ( GlobalBlock $blockObject, $maxIPsToAutoblock, &$ipsToAutoblock ) use ( $testUser ) {
				$this->assertTrue( $testUser->equals( $blockObject->getTargetUserIdentity() ) );
				$this->assertSame( 2, $maxIPsToAutoblock );
				$ipsToAutoblock[] = '1.2.3.4';
			}
		);
		// Perform the global block on the target user
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$status = $globalBlockManager->block(
			$testUser->getName(),
			$data['reason'],
			$data['expiry'],
			$this->getMutableTestUser( 'steward' )->getUser(),
			$data['options']
		);
		if ( $expectedError !== '' ) {
			$this->assertStatusMessage( $expectedError, $status );
		} else {
			$actual = $this->getGlobalBlock( $testUser->getName() );
			$this->assertSame( $data['reason'], $actual['reason'] );
			$this->assertSame( $data['expiry'], $actual['expiry'] );
			$this->assertSame( 0, (int)$actual['anon-only'] );
			$this->assertGlobalBlockOptionApplied( $data, 'allow-account-creation', '1', '0', $actual );
			$this->assertGlobalBlockOptionApplied( $data, 'enable-autoblock', '1', '0', $actual );
			// Assert that a log entry was added to the 'logging' table for the block
			$this->assertThatLogWasAdded(
				$testUser->getName(), 'gblock',
				'A logging entry for the global block was not found in the logging table.'
			);
			if ( in_array( 'enable-autoblock', $data['options'] ) ) {
				// Check that if autoblocking was enabled, a retroactive global autoblock is created on 1.2.3.4
				$this->newSelectQueryBuilder()
					->select( '1' )
					->from( 'globalblocks' )
					->where( [ 'gb_address' => '1.2.3.4', 'gb_autoblock_parent_id' => $status->getValue()['id'] ] )
					->caller( __METHOD__ )
					->assertFieldValue( 1 );
			} else {
				// Assert that no autoblocks were created if autoblocking is disabled
				$this->newSelectQueryBuilder()
					->select( '1' )
					->from( 'globalblocks' )
					->where( [ 'gb_address' => '1.2.3.4' ] )
					->caller( __METHOD__ )
					->assertEmptyResult();
			}
		}
	}

	public static function provideBlockForExistingUser() {
		return [
			'good' => [
				'data' => [
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [],
				],
				'expectedError' => '',
			],
			'good with account creation enabled' => [
				'data' => [
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'allow-account-creation' ],
				],
				'expectedError' => '',
			],
			'good with autoblocking enabled' => [
				'data' => [
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'enable-autoblock' ],
				],
				'expectedError' => '',
			],
			'Attempted to block account with anon-only set' => [
				'data' => [
					'reason' => 'Test block1234',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-anononly-on-account',
			],
			'bad expiry' => [
				'data' => [
					'reason' => 'Test block',
					'expiry' => '2021-03-06T25:00:00Z',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-expiryinvalid',
			],
		];
	}

	public function testBlockModificationUsingGlobalBlockId() {
		// Create a testing block on 1.2.3.6 so that we can test block modification.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$globalBlockId = $globalBlockManager->block(
			'1.2.3.6',
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			[ 'anon-only' ]
		)->getValue()['id'];

		$this->assertStatusGood( $globalBlockManager->block(
			'#' . $globalBlockId,
			'Test block2',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			[ 'modify' ]
		) );
		$actual = $this->getGlobalBlock( '1.2.3.6' );
		$this->assertSame( 'Test block2', $actual[ 'reason' ] );
		$this->assertSame( '0', $actual[ 'anon-only' ] );
		// Assert that a log entry was added to the 'logging' table for the block
		$this->assertThatLogWasAdded(
			'#' . $globalBlockId, 'modify',
			'A logging entry for the global block was not found in the logging table.'
		);
	}

	public function testBlockModificationToDisableAutoblocking() {
		// Create a test global block on an existing user which enables autoblocks.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$targetUserIdentity = $this->getTestUser()->getUserIdentity();
		$parentBlockStatus = $globalBlockManager->block(
			$targetUserIdentity->getName(), 'testing', 'infinity', $this->getTestUser( 'steward' )->getUser(),
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $parentBlockStatus );
		$parentBlockId = $parentBlockStatus->getValue()['id'];
		// Create an autoblock for the user block created above
		$autoblockStatus = $globalBlockManager->autoblock( $parentBlockId, '1.2.3.6' );
		$this->assertStatusGood( $autoblockStatus );
		$autoblockId = $autoblockStatus->getValue()['id'];
		// Verify that the autoblock actually exists in the DB, so that we can be sure we saw a change in the DB later
		// in the test.
		$this->newSelectQueryBuilder()
			->select( '1' )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $autoblockId ] )
			->assertFieldValue( 1 );
		// Modify the parent block to now disable autoblocking.
		$parentBlockModifyStatus = $globalBlockManager->block(
			'#' . $parentBlockId, 'modify test', 'infinity', $this->getTestUser( 'steward' )->getUser(),
			[ 'modify' ]
		);
		$this->assertStatusGood( $parentBlockModifyStatus );
		// Check that the autoblock we created has been removed as a consequence of modifying the block to remove
		// autoblocking.
		$this->newSelectQueryBuilder()
			->select( '1' )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $autoblockId ] )
			->assertEmptyResult();
	}

	private function assertAutoblockParametersAreAsExpected( $autoblockIds, $expectedRows ) {
		$this->newSelectQueryBuilder()
			->select( [ 'gb_id', 'gb_by_central_id', 'gb_create_account', 'gb_reason', 'gb_expiry' ] )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $autoblockIds ] )
			->caller( __METHOD__ )
			->assertResultSet( $expectedRows );
	}

	public function testBlockModificationAlsoModifiesPropertiesOfAssociatedGlobalAutoblocks() {
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'qqx' );
		$this->overrideConfigValue( 'GlobalBlockingAutoblockExpiry', "86400" );
		// Create a test global block on an existing user which enables autoblocks.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$targetUserIdentity = $this->getTestUser()->getUserIdentity();
		$firstPerformer = $this->getTestUser( 'steward' )->getUser();
		$parentBlockStatus = $globalBlockManager->block(
			$targetUserIdentity->getName(), 'testing', 'infinity', $firstPerformer,
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $parentBlockStatus );
		$parentBlockId = $parentBlockStatus->getValue()['id'];
		// Create an autoblock for the user block created above
		$firstAutoblockStatus = $globalBlockManager->autoblock( $parentBlockId, '1.2.3.6' );
		$this->assertStatusGood( $firstAutoblockStatus );
		$firstAutoblockId = $firstAutoblockStatus->getValue()['id'];
		// Create an another autoblock for the user block created above with a short expiry. Set the autoblock expiry
		// maximum length to a short value to make the expiry short.
		$this->overrideConfigValue( 'GlobalBlockingAutoblockExpiry', "10" );
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$secondAutoblockStatus = $globalBlockManager->autoblock( $parentBlockId, '1.2.3.7' );
		$this->assertStatusGood( $secondAutoblockStatus );
		$secondAutoblockId = $secondAutoblockStatus->getValue()['id'];
		// Verify that the autoblock actually exists in the DB and that the properties are expected. This is necessary
		// so that we can check for a change in the autoblock parameters after the parent block modification later in
		// this test.
		$expectedAutoblockReason = "(globalblocking-autoblocker: {$targetUserIdentity->getName()}, testing)";
		$this->assertAutoblockParametersAreAsExpected(
			[ $firstAutoblockId, $secondAutoblockId ],
			[
				[ $firstAutoblockId, $firstPerformer->getId(), 1, $expectedAutoblockReason, '20210303220000' ],
				[ $secondAutoblockId, $firstPerformer->getId(), 1, $expectedAutoblockReason, '20210302220010' ],
			]
		);
		// Modify the parent block to change most properties but keep the same target and autoblocking enabled.
		$this->overrideConfigValue( 'GlobalBlockingAutoblockExpiry', "86400" );
		$secondPerformer = $this->getMutableTestUser( 'steward' )->getUser();
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$parentBlockModifyStatus = $globalBlockManager->block(
			'#' . $parentBlockId, 'modify test', '2 hours', $secondPerformer,
			[ 'modify', 'enable-autoblock', 'allow-account-creation' ]
		);
		$this->assertStatusGood( $parentBlockModifyStatus );
		// Check that the autoblock still exists, but has been modified to match the changed parameters of the
		// parent block
		$expectedAutoblockReason = "(globalblocking-autoblocker: {$targetUserIdentity->getName()}, modify test)";
		$this->assertAutoblockParametersAreAsExpected(
			[ $firstAutoblockId, $secondAutoblockId ],
			[
				[ $firstAutoblockId, $secondPerformer->getId(), 0, $expectedAutoblockReason, '20210303000000' ],
				[ $secondAutoblockId, $secondPerformer->getId(), 0, $expectedAutoblockReason, '20210302220010' ],
			]
		);
		// Modify the parent block to again have an infinite expiry and check that nothing changed for the autoblocks.
		$parentBlockModifyStatus = $globalBlockManager->block(
			'#' . $parentBlockId, 'modify test', 'infinity', $secondPerformer,
			[ 'modify', 'enable-autoblock', 'allow-account-creation' ]
		);
		$this->assertStatusGood( $parentBlockModifyStatus );
		$this->assertAutoblockParametersAreAsExpected(
			[ $firstAutoblockId, $secondAutoblockId ],
			[
				[ $firstAutoblockId, $secondPerformer->getId(), 0, $expectedAutoblockReason, '20210303000000' ],
				[ $secondAutoblockId, $secondPerformer->getId(), 0, $expectedAutoblockReason, '20210302220010' ],
			]
		);
	}

	/** @dataProvider provideUnblock */
	public function testUnblock( array $data, string $expectedError ) {
		// Prepare target
		$target = '1.2.3.4';

		// Prepare options
		$options = [ 'anon-only' ];

		// To ensure there is a placed block so that we can attempt to unblock
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockingServices->getGlobalBlockManager()->block(
			$target,
			'Block for testing unblock',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			$options
		);

		$errors = $globalBlockingServices->getGlobalBlockManager()->unblock(
			$data[ 'target' ],
			$data[ 'reason' ],
			$this->getMutableTestUser( 'steward' )->getUser()
		);
		if ( $expectedError !== '' ) {
			$this->assertStatusMessage( $expectedError, $errors );
		} else {
			$actual = $this->getGlobalBlock( $data[ 'target' ] );
			$this->assertArrayEquals( [], $actual );
			// Assert that a log entry was added to the 'logging' table for the unblock
			$this->assertThatLogWasAdded(
				$data[ 'target' ],
				'gunblock',
				'A logging entry for the unblock was not found in the logging table.'
			);
		}
	}

	public static function provideUnblock() {
		return [
			'good' => [
				'data' => [
					'target' => '1.2.3.4',
					'reason' => 'Test unblock',
				],
				'expectedError' => '',
			],
			'Invalid username' => [
				'data' => [
					'target' => 'Template:Test User#test',
					'reason' => 'Test unblock',
				],
				'expectedError' => 'globalblocking-block-target-invalid',
			],
			'not blocked' => [
				'data' => [
					'target' => '1.2.3.5',
					'reason' => 'Test unblock',
				],
				'expectedError' => 'globalblocking-notblocked',
			],
		];
	}

	public function testUnblockUsingGlobalBlockId() {
		// Create a testing block on 1.2.3.6 so that we can test block modification.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$globalBlockId = $globalBlockManager->block(
			'1.2.3.6',
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			[ 'anon-only' ]
		)->getValue()['id'];

		$this->assertStatusGood( $globalBlockManager->unblock(
			'#' . $globalBlockId,
			'Test unblock',
			$this->getMutableTestUser( 'steward' )->getUser()
		) );
		$actual = $this->getGlobalBlock( '1.2.3.6' );
		$this->assertArrayEquals( [], $actual );
		// Assert that a log entry was added to the 'logging' table for the unblock
		$this->assertThatLogWasAdded(
			'#' . $globalBlockId,
			'gunblock',
			'A logging entry for the unblock was not found in the logging table.'
		);
	}

	public function testUnblockUsingGlobalBlockIdWhichDoesNotExist() {
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$unblockStatus = $globalBlockManager->unblock(
			'#12344556',
			'Test unblock',
			$this->getMutableTestUser( 'steward' )->getUser()
		);
		$this->assertStatusError( 'globalblocking-notblocked-id', $unblockStatus );
	}

	private function assertGlobalBlocksTableEmpty() {
		$this->newSelectQueryBuilder()
			->select( 'count(*)' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValue( 0 );
	}

	public function testUnblockForAccountBlockWithAssociatedGlobalAutoblocks() {
		// Create a testing block on a user and then create an autoblock for that user block.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$testTarget = $this->getTestUser()->getUserIdentity()->getName();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUser();
		$globalAccountBlockStatus = $globalBlockManager->block(
			$testTarget, 'test1234', 'infinite', $testPerformer,
			[ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalAccountBlockStatus );
		$accountGlobalBlockId = $globalAccountBlockStatus->getValue()['id'];
		$autoBlockStatus = $globalBlockManager->autoblock( $accountGlobalBlockId, '7.8.9.0' );
		$this->assertStatusGood( $autoBlockStatus );
		$autoBlockId = $autoBlockStatus->getValue()['id'];
		// Validate that the global account block and autoblock exist, so that we can check that they get deleted
		// by the code we are testing
		$this->newSelectQueryBuilder()
			->select( 'gb_id' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValues( [ (string)$accountGlobalBlockId, (string)$autoBlockId ] );
		// Call ::unblock on the global account block
		$globalBlockManager->unblock( $testTarget, 'testing', $testPerformer );
		// Check that both the account block and autoblock are now deleted from the globalblocks database table.
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testUnblockForGlobalAutoblock() {
		// Create a testing block on a user and then create an autoblock for that user block.
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUser();
		$globalAccountBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test1234', 'infinite',
			$testPerformer, [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $globalAccountBlockStatus );
		$accountGlobalBlockId = $globalAccountBlockStatus->getValue()['id'];
		$autoBlockStatus = $globalBlockManager->autoblock( $accountGlobalBlockId, '7.8.9.10' );
		$this->assertStatusGood( $autoBlockStatus );
		$autoBlockId = $autoBlockStatus->getValue()['id'];
		// Check that the DB is set up correctly for the test
		$this->newSelectQueryBuilder()
			->select( 'gb_id' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValues( [ (string)$accountGlobalBlockId, (string)$autoBlockId ] );
		// Attempt to unblock the global autoblock using the ID as the target
		$unblockStatus = $globalBlockManager->unblock( '#' . $autoBlockId, 'test', $testPerformer );
		$this->assertStatusGood( $unblockStatus );
		// Check that the autoblock has been removed, but not the user block
		$this->newSelectQueryBuilder()
			->select( 'gb_id' )
			->from( 'globalblocks' )
			->caller( __METHOD__ )
			->assertFieldValue( $accountGlobalBlockId );
	}

	/** @dataProvider provideInvalidIPAddresses */
	public function testAutoblockForInvalidIP( $invalidIP ) {
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$actualStatus = $globalBlockManager->autoblock( 0, $invalidIP );
		$this->assertInstanceOf( RawMessage::class, $actualStatus->getMessages()[0] );
		$this->assertSame(
			'IP provided for autoblocking is invalid.',
			$actualStatus->getMessages()[0]->fetchMessage()
		);
		$this->assertGlobalBlocksTableEmpty();
	}

	public static function provideInvalidIPAddresses() {
		return [
			'String which is not in any IP format' => [ 'abc' ],
			'IP range' => [ '1.2.3.4/23' ],
		];
	}

	public function testAutoblockForGloballyExemptIP() {
		// Mock the GlobalBlockingGlobalAutoblockExemptionListProvider service to say that the IP is exempt
		$mockGlobalAutoblockExemptionListProvider = $this->createMock(
			GlobalBlockingGlobalAutoblockExemptionListProvider::class
		);
		$mockGlobalAutoblockExemptionListProvider->method( 'isExempt' )
			->with( '1.2.3.4' )
			->willReturn( true );
		$this->setService(
			'GlobalBlocking.GlobalBlockingGlobalAutoblockExemptionListProvider',
			$mockGlobalAutoblockExemptionListProvider
		);
		// Call the method under test
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$this->assertStatusGood( $globalBlockManager->autoblock( 0, '1.2.3.4' ) );
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testAutoblockWhenFeatureFlagDisabled() {
		$this->overrideConfigValue( 'GlobalBlockingEnableAutoblocks', false );
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$actualStatus = $globalBlockManager->autoblock( 0, '1.2.3.4' );
		$this->assertStatusGood( $actualStatus );
		$this->assertGlobalBlocksTableEmpty();
	}

	public function testAutoblockForMissingParentBlock() {
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$this->assertStatusError( 'globalblocking-notblocked-id', $globalBlockManager->autoblock( 0, '1.2.3.4' ) );
		$this->assertGlobalBlocksTableEmpty();
	}

	private function assertNoAutoblockCreated() {
		$this->newSelectQueryBuilder()
			->select( 'count(*)' )
			->from( 'globalblocks' )
			->where( $this->getDb()->expr( 'gb_autoblock_parent_id', '!=', 0 ) )
			->caller( __METHOD__ )
			->assertFieldValue( 0 );
	}

	public function testAutoblockWhenParentBlockDoesNotHaveAutoblocksEnabled() {
		// Create a parent global block with autoblocking disabled
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$parentBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test', 'infinity',
			$this->getTestUser( 'steward' )->getUser()
		);
		$this->assertStatusGood( $parentBlockStatus );
		$parentBlockId = $parentBlockStatus->getValue()['id'];
		// Call the method under test
		$this->assertStatusGood( $globalBlockManager->autoblock( $parentBlockId, '1.2.3.4' ) );
		$this->assertNoAutoblockCreated();
	}

	public function testAutoblockWhenIPAlreadyManuallyBlocked() {
		// Create a parent global block with autoblocking enabled
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$parentBlockStatus = $globalBlockManager->block(
			$this->getTestUser()->getUserIdentity()->getName(), 'test', 'infinity',
			$this->getTestUser( 'steward' )->getUser(), [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $parentBlockStatus );
		$parentBlockId = $parentBlockStatus->getValue()['id'];
		// Create a block on the IP address that will will attempt to autoblock later in the test
		$ipBlockStatus = $globalBlockManager->block(
			'1.2.3.4', 'test IP block', '1 week',
			$this->getTestUser( 'steward' )->getUser(), []
		);
		$this->assertStatusGood( $ipBlockStatus );
		$ipBlockId = $ipBlockStatus->getValue()['id'];
		// Call the method under test
		$this->assertStatusGood( $globalBlockManager->autoblock( $parentBlockId, '1.2.3.4' ) );
		$this->assertNoAutoblockCreated();
		// Check that the non-autoblock block on the IP remains untouched after calling the method under test.
		$ipBlockStillExists = (bool)$this->getDb()->newSelectQueryBuilder()
			->select( '1' )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $ipBlockId ] )
			->caller( __METHOD__ )
			->fetchField();
		$this->assertTrue( $ipBlockStillExists );
	}

	public function testAutoblock() {
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'qqx' );
		$this->overrideConfigValue( 'GlobalBlockingAutoblockExpiry', 86400 );
		// Create a parent global block with autoblocking enabled
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();
		$parentBlockTarget = $this->getMutableTestUser()->getUserIdentity()->getName();
		$performer = $this->getTestUser( 'steward' )->getUser();
		$parentBlockStatus = $globalBlockManager->block(
			$parentBlockTarget, 'test', 'infinity', $performer, [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $parentBlockStatus );
		$parentBlockId = $parentBlockStatus->getValue()['id'];
		// Create an autoblock on the target, asserting that the autoblock worked.
		$firstAutoblockStatus = $globalBlockManager->autoblock( $parentBlockId, '1.2.3.4' );
		$this->assertStatusGood( $firstAutoblockStatus );
		$firstAutoblockId = $firstAutoblockStatus->getValue()['id'];
		$this->assertArrayEquals(
			[
				'reason' => "(globalblocking-autoblocker: $parentBlockTarget, test)", 'anon-only' => '0',
				'allow-account-creation' => '0', 'enable-autoblock' => '0', 'parentBlockId' => $parentBlockId,
				'expiry' => '2021-03-03T22:00:00Z', 'timestamp' => '2021-03-02T22:00:00Z',
				'blocker' => [ 'byCentralId' => $performer->getId(), 'byWiki' => WikiMap::getCurrentWikiId() ],
			],
			$this->getGlobalBlock( '#' . $firstAutoblockId ),
			false,
			true
		);
		// Try to create another autoblock on the same IP, but for a different parent block that expires before the
		// current autoblock ends.
		$secondParentBlockStatus = $globalBlockManager->block(
			$this->getMutableTestUser()->getUserIdentity()->getName(), 'test', '4 hours',
			$performer, [ 'enable-autoblock' ]
		);
		$this->assertStatusGood( $secondParentBlockStatus );
		$secondParentBlockId = $secondParentBlockStatus->getValue()['id'];
		$secondAutoblockStatus = $globalBlockManager->autoblock( $secondParentBlockId, '1.2.3.4' );
		$this->assertStatusGood( $secondAutoblockStatus );
		$this->assertStatusValue( null, $secondAutoblockStatus );
		// Check that the attempt to autoblock the IP again has not caused any changes to the first autoblock.
		$this->assertArrayEquals(
			[
				'reason' => "(globalblocking-autoblocker: $parentBlockTarget, test)", 'anon-only' => '0',
				'allow-account-creation' => '0', 'enable-autoblock' => '0', 'parentBlockId' => $parentBlockId,
				'expiry' => '2021-03-03T22:00:00Z', 'timestamp' => '2021-03-02T22:00:00Z',
				'blocker' => [ 'byCentralId' => $performer->getId(), 'byWiki' => WikiMap::getCurrentWikiId() ],
			],
			$this->getGlobalBlock( '#' . $firstAutoblockId ),
			false,
			true
		);
		// Call ::autoblock again, after moving the time on and check that the autoblock expiry and timestamp have
		// been updated.
		ConvertibleTimestamp::setFakeTime( '2021-03-03T12:00:00Z' );
		$thirdAutoblockStatus = $globalBlockManager->autoblock( $parentBlockId, '1.2.3.4' );
		$this->assertStatusGood( $thirdAutoblockStatus );
		$this->assertStatusValue( null, $secondAutoblockStatus );
		$this->assertArrayEquals(
			[
				'reason' => "(globalblocking-autoblocker: $parentBlockTarget, test)", 'anon-only' => '0',
				'allow-account-creation' => '0', 'enable-autoblock' => '0', 'parentBlockId' => $parentBlockId,
				'expiry' => '2021-03-04T12:00:00Z', 'timestamp' => '2021-03-03T12:00:00Z',
				'blocker' => [ 'byCentralId' => $performer->getId(), 'byWiki' => WikiMap::getCurrentWikiId() ],
			],
			$this->getGlobalBlock( '#' . $firstAutoblockId ),
			false,
			true
		);
	}
}
