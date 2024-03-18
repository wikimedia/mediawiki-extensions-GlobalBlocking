<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
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
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
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
			->select( [ 'gb_anon_only', 'gb_reason', 'gb_expiry' ] )
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
			$blockOptions['reason'] = $block->gb_reason;
			$blockOptions['expiry'] = ( $block->gb_expiry === 'infinity' )
				? 'infinity'
				: wfTimestamp( TS_ISO_8601, $block->gb_expiry );
		}

		return $blockOptions;
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
					'log_title' => Title::makeTitleSafe( NS_USER, $target )->getDBkey(),
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
	public function testBlock( array $data, string $expectedError, bool $globalAccountBlocksEnabled ) {
		$this->setMwGlobals( 'wgGlobalBlockingAllowGlobalAccountBlocks', $globalAccountBlocksEnabled );
		// Prepare target for default block
		$target = '1.2.3.6';

		// Prepare options for default block
		$options = [ 'anon-only' ];

		// To ensure there is a placed block so that we can attempt to reblock it without modify
		// being set
		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockManager();
		$globalBlockManager->block(
			$target,
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			$options
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
				'globalAccountBlocksEnabled' => true,
			],
			'good range' => [
				'data' => [
					'target' => '1.2.3.0/24',
					'reason' => 'Test block1234',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => '',
				'globalAccountBlocksEnabled' => true,
			],
			'good modify' => [
				'data' => [
					'target' => '1.2.3.6',
					'reason' => 'Test block1',
					'expiry' => '2021-03-06T23:00:00Z',
					'options' => [ 'anon-only', 'modify' ],
				],
				'expectedError' => '',
				'globalAccountBlocksEnabled' => true,
			],
			'bad target' => [
				'data' => [
					'target' => 'Test User',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [],
				],
				'expectedError' => 'globalblocking-block-ipinvalid',
				'globalAccountBlocksEnabled' => false,
			],
			'no such user target' => [
				'data' => [
					'target' => 'Nonexistent User',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-target-invalid',
				'globalAccountBlocksEnabled' => true,
			],
			'bad expiry' => [
				'data' => [
					'target' => '1.2.3.5',
					'reason' => 'Test block',
					'expiry' => '2021-03-06T25:00:00Z',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-expiryinvalid',
				'globalAccountBlocksEnabled' => true,
			],
			'bad range' => [
				'data' => [
					'target' => '1.0.0.0/10',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-bigrange',
				'globalAccountBlocksEnabled' => true,
			],
			'no modify' => [
				'data' => [
					'target' => '1.2.3.6',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-alreadyblocked',
				'globalAccountBlocksEnabled' => true,
			],
		];
	}

	/** @dataProvider provideBlockForExistingUser */
	public function testBlockForExistingUser( array $data, string $expectedError ) {
		$this->setMwGlobals( 'wgGlobalBlockingAllowGlobalAccountBlocks', true );
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
			'Attempted to block account with anon-only set' => [
				'data' => [
					'reason' => 'Test block1234',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-anononly-on-account',
				'globalAccountBlocksEnabled' => true,
			],
			'bad expiry' => [
				'data' => [
					'reason' => 'Test block',
					'expiry' => '2021-03-06T25:00:00Z',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-expiryinvalid',
				'globalAccountBlocksEnabled' => true,
			],
		];
	}

	/** @dataProvider provideUnblock */
	public function testUnblock( array $data, string $expectedError, bool $globalAccountBlocksEnabled ) {
		$this->setMwGlobals( 'wgGlobalBlockingAllowGlobalAccountBlocks', $globalAccountBlocksEnabled );
		// Prepare target
		$target = '1.2.3.4';

		// Prepare options
		$options = [ 'anon-only' ];

		// To ensure there is a placed block so that we can attempt to unblock
		GlobalBlocking::block(
			$target,
			'Block for testing unblock',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			$options
		);

		$errors = GlobalBlocking::unblock(
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
				'globalAccountBlocksEnabled' => true,
			],
			'bad target' => [
				'data' => [
					'target' => 'Test User',
					'reason' => 'Test unblock',
				],
				'expectedError' => 'globalblocking-block-ipinvalid',
				'globalAccountBlocksEnabled' => false,
			],
			'not blocked' => [
				'data' => [
					'target' => '1.2.3.5',
					'reason' => 'Test unblock',
				],
				'expectedError' => 'globalblocking-notblocked',
				'globalAccountBlocksEnabled' => false,
			],
			'not blocked when account blocks enabled' => [
				'data' => [
					'target' => '1.2.3.5',
					'reason' => 'Test unblock',
				],
				'expectedError' => 'globalblocking-notblocked-new',
				'globalAccountBlocksEnabled' => true,
			],
		];
	}

}
