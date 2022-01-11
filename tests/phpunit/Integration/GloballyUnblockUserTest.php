<?php

use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;

/**
 * @coversDefaultClass MediaWiki\Extension\GlobalBlocking\GlobalBlocking
 * @group Database
 */
class GloballyUnblockUserTest extends MediaWikiIntegrationTestCase {
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
	 * @covers ::unblock
	 */
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

	/**
	 * @return array[]
	 */
	public function validationProvider() {
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
