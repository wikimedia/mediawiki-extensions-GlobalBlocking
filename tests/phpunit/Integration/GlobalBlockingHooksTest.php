<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use Config;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Permissions\PermissionManager;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks
 */
class GlobalBlockingHooksTest extends MediaWikiIntegrationTestCase {
	private function getGlobalBlockingHooks(): GlobalBlockingHooks {
		return new GlobalBlockingHooks(
			$this->createMock( PermissionManager::class ),
			$this->createMock( Config::class ),
			$this->createMock( CommentFormatter::class )
		);
	}

	public function testConstructor() {
		$hooks = $this->getGlobalBlockingHooks();
		$this->assertInstanceOf( GlobalBlockingHooks::class, $hooks );
	}

	/**
	 * @dataProvider provideOnGetBlockErrorMessageKey
	 */
	public function testOnGetBlockErrorMessageKey( $xff, $range, $expectedKey ) {
		$key = 'blockedtext';
		$block = $this->createMock( GlobalBlock::class );
		$block->method( 'getXff' )
			->willReturn( $xff );
		$block->method( 'getTargetName' )
			->willReturn( $range ? '1.2.3.4/24' : '1.2.3.4' );

		$hooks = $this->getGlobalBlockingHooks();
		$result = $hooks->onGetBlockErrorMessageKey( $block, $key );

		$this->assertFalse( $result );
		$this->assertSame( $expectedKey, $key );
	}

	public static function provideOnGetBlockErrorMessageKey() {
		return [
			'IP block' => [
				'xff' => false,
				'range' => false,
				'expectedKey' => 'globalblocking-blockedtext-ip',
			],
			'IP range block' => [
				'xff' => false,
				'range' => true,
				'expectedKey' => 'globalblocking-blockedtext-range',
			],
			'XFF block' => [
				'xff' => true,
				'range' => false,
				'expectedKey' => 'globalblocking-blockedtext-xff',
			],
		];
	}
}
