<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWikiIntegrationTestCase;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager
 * @group Database
 */
class GlobalBlockManagerTest extends MediaWikiIntegrationTestCase {
	use MockAuthorityTrait;

	public function setUp(): void {
		ConvertibleTimestamp::setFakeTime( '2021-03-02T22:00:00Z' );
	}

	/**
	 * @param string $address
	 * @return array
	 */
	private function getGlobalBlock( string $address ) {
		$blockOptions = [];
		$dbr = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalBlockingConnectionProvider()
			->getReplicaGlobalBlockingDatabase();
		$block = $dbr->newSelectQueryBuilder()
			->select( [ 'gb_anon_only', 'gb_reason', 'gb_expiry' ] )
			->from( 'globalblocks' )
			->where( [
				'gb_address' => $address,
				$dbr->expr( 'gb_expiry', '>', $dbr->timestamp() ),
			] )
			->caller( __METHOD__ )
			->fetchRow();
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
					'log_title' => $target,
				] )
				->caller( __METHOD__ )
				->fetchField(),
			$failMessage
		);
	}

	/** @dataProvider provideBlock */
	public function testBlock( array $data, string $expectedError ) {
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
			'bad target' => [
				'data' => [
					'target' => 'Test User',
					'reason' => 'Test block',
					'expiry' => 'infinity',
					'options' => [ 'anon-only' ],
				],
				'expectedError' => 'globalblocking-block-ipinvalid',
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

	/** @dataProvider provideUnblock */
	public function testUnblock( array $data, string $expectedError ) {
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
			'bad target' => [
				'data' => [
					'target' => 'Test User',
					'reason' => 'Test unblock',
				],
				'expectedError' => 'globalblocking-block-ipinvalid',
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

}
