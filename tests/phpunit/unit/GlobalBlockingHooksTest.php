<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit;

use CentralIdLookup;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Config\Config;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
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
			$this->createMock( GlobalBlockingLinkBuilder::class )
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

	public function testOnSpecialContributionsBeforeMainOutputForNonIP() {
		$hooks = $this->getGlobalBlockingHooks();
		// Create a mock user that is not an IP
		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'getName' )
			->willReturn( 'Test' );
		// Test that the hook does nothing for non-IP users. Because this is a unit test, it should throw
		// on the access to the database if this test fails.
		$this->assertTrue( $hooks->onSpecialContributionsBeforeMainOutput(
			1, $mockUser, $this->createMock( SpecialPage::class )
		) );
	}
}
