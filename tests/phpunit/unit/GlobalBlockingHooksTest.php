<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit;

use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Config\Config;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks
 */
class GlobalBlockingHooksTest extends MediaWikiUnitTestCase {
	private function getGlobalBlockingHooks(): GlobalBlockingHooks {
		return new GlobalBlockingHooks(
			$this->createMock( PermissionManager::class ),
			$this->createMock( Config::class ),
			$this->createMock( CommentFormatter::class ),
			$this->createMock( CentralIdLookup::class ),
			$this->createMock( GlobalBlockingLinkBuilder::class ),
			$this->createMock( GlobalBlockLookup::class ),
			$this->createMock( GlobalBlockingConnectionProvider::class ),
			$this->createMock( GlobalBlockLocalStatusLookup::class )
		);
	}

	public function testConstructor() {
		$hooks = $this->getGlobalBlockingHooks();
		$this->assertInstanceOf( GlobalBlockingHooks::class, $hooks );
	}

	/**
	 * @dataProvider provideOnGetBlockErrorMessageKey
	 */
	public function testOnGetBlockErrorMessageKey( $xff, $target, $expectedKey ) {
		$key = 'blockedtext';
		$block = $this->createMock( GlobalBlock::class );
		$block->method( 'getXff' )
			->willReturn( $xff );
		$block->method( 'getTargetName' )
			->willReturn( $target );

		$hooks = $this->getGlobalBlockingHooks();
		$result = $hooks->onGetBlockErrorMessageKey( $block, $key );

		$this->assertFalse( $result );
		$this->assertSame( $expectedKey, $key );
	}

	public static function provideOnGetBlockErrorMessageKey() {
		return [
			'IP block' => [
				'xff' => false,
				'target' => '1.2.3.4',
				'expectedKey' => 'globalblocking-blockedtext-ip',
			],
			'IP range block' => [
				'xff' => false,
				'target' => '1.2.3.4/24',
				'expectedKey' => 'globalblocking-blockedtext-range',
			],
			'XFF block' => [
				'xff' => true,
				'target' => '1.2.3.4',
				'expectedKey' => 'globalblocking-blockedtext-xff',
			],
			'Account block' => [
				'xff' => false,
				'target' => 'Test',
				'expectedKey' => 'globalblocking-blockedtext-user',
			],
		];
	}
}
