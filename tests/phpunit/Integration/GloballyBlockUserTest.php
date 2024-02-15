<?php

use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;

/**
 * @coversDefaultClass MediaWiki\Extension\GlobalBlocking\GlobalBlocking
 * @group Database
 */
class GloballyBlockUserTest extends MediaWikiIntegrationTestCase {
	use MockAuthorityTrait;

	/**
	 * @param string $address
	 * @return array
	 */
	private function getGlobalBlock( string $address ) {
		$blockOptions = [];
		$dbr = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );
		$block = $dbr->selectRow( 'globalblocks',
			[ 'gb_anon_only', 'gb_reason', 'gb_expiry' ],
			[
				'gb_address' => $address,
				'gb_expiry >' . $dbr->addQuotes( $dbr->timestamp( wfTimestampNow() ) ),
			],
			__METHOD__
		);
		if ( $block ) {
			$blockOptions['anon-only'] = $block->gb_anon_only;
			$blockOptions['reason'] = $block->gb_reason;
			$blockOptions['expiry'] = ( $block->gb_expiry === 'infinity' )
				? 'infinity'
				: wfTimestamp( TS_ISO_8601, $block->gb_expiry );
		}

		return $blockOptions;
	}

	/**
	 * @dataProvider validationProvider
	 * @param array $data
	 * @param string $expectedError
	 * @covers ::block
	 */
	public function testBlock( array $data, string $expectedError ) {
		// Prepare target for default block
		$target = '1.2.3.6';

		// Prepare options for default block
		$options = [ 'anon-only' ];

		// To ensure there is a placed block so that we can attempt to reblock it without modify
		// being set
		GlobalBlocking::block(
			$target,
			'Test block',
			'infinity',
			$this->getMutableTestUser( 'steward' )->getUser(),
			$options
		);

		$errors = GlobalBlocking::block(
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
		}
	}

	/**
	 * @return array[]
	 */
	public function validationProvider() {
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

}
