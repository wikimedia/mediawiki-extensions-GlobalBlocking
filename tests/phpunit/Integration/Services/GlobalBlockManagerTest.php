<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
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
			->select( [ 'gb_anon_only', 'gb_reason', 'gb_expiry', 'gb_create_account' ] )
			->from( 'globalblocks' )
			->where( [
				'gb_address' => $target,
				$dbr->expr( 'gb_expiry', '>', $dbr->timestamp() ),
			] )
			->caller( __METHOD__ );
		if ( !IPUtils::isIPAddress( $target ) ) {
			// Used to assert that the central ID column is set correctly.
			$queryBuilder->where( [
				'gb_target_central_id' => $this->getServiceContainer()
					->getCentralIdLookup()->centralIdFromName( $target )
			] );
		}
		$block = $queryBuilder->fetchRow();
		if ( $block ) {
			$blockOptions['anon-only'] = $block->gb_anon_only;
			$blockOptions['create-account'] = $block->gb_create_account;
			$blockOptions['reason'] = $block->gb_reason;
			$blockOptions['expiry'] = ( $block->gb_expiry === 'infinity' )
				? 'infinity'
				: wfTimestamp( TS_ISO_8601, $block->gb_expiry );
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
			if ( in_array( 'anon-only', $data[ 'options' ] ) ) {
				$expectedAnon = '1';
			} else {
				$expectedAnon = '0';
			}
			$this->assertSame( $expectedAnon, $actual[ 'anon-only' ] );
			if ( in_array( 'allow-account-creation', $data['options'] ) ) {
				$expectedCreateAccount = '0';
			} else {
				$expectedCreateAccount = '1';
			}
			$this->assertSame( $expectedCreateAccount, $actual[ 'create-account' ] );
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
		];
	}

	/** @dataProvider provideBlockForExistingUser */
	public function testBlockForExistingUser( array $data, string $expectedError ) {
		$testUser = $this->getTestUser()->getUser();
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$errors = $globalBlockManager->block(
			$testUser->getName(),
			$data['reason'],
			$data['expiry'],
			$this->getMutableTestUser( 'steward' )->getUser(),
			$data['options']
		);
		if ( $expectedError !== '' ) {
			$this->assertStatusMessage( $expectedError, $errors );
		} else {
			$actual = $this->getGlobalBlock( $testUser->getName() );
			$this->assertSame( $data['reason'], $actual['reason'] );
			$this->assertSame( $data['expiry'], $actual['expiry'] );
			$this->assertSame( 0, (int)$actual['anon-only'] );
			// Assert that a log entry was added to the 'logging' table for the block
			$this->assertThatLogWasAdded(
				$testUser->getName(), 'gblock',
				'A logging entry for the global block was not found in the logging table.'
			);
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
}
