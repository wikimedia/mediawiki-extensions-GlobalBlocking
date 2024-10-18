<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserNameUtils;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingHooks
 */
class GlobalBlockingHooksTest extends MediaWikiUnitTestCase {
	private function getGlobalBlockingHooks( $overrides = [] ): GlobalBlockingHooks {
		return new GlobalBlockingHooks(
			$overrides['config'] ?? $this->createMock( Config::class ),
			$this->createMock( CommentFormatter::class ),
			$this->createMock( CentralIdLookup::class ),
			$this->createMock( GlobalBlockingLinkBuilder::class ),
			$this->createMock( GlobalBlockLookup::class ),
			$this->createMock( UserNameUtils::class ),
			$this->createMock( GlobalBlockingUserVisibilityLookup::class ),
			$this->createMock( GlobalBlockManager::class ),
			$this->createMock( GlobalBlockingGlobalBlockDetailsRenderer::class ),
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
	public function testOnGetBlockErrorMessageKey( $xff, $target, $type, $expectedKey ) {
		$key = 'blockedtext';
		$block = $this->createMock( GlobalBlock::class );
		$block->method( 'getXff' )
			->willReturn( $xff );
		$block->method( 'getTargetName' )
			->willReturn( $target );
		$block->method( 'getType' )
			->willReturn( $type );

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
				'type' => Block::TYPE_IP,
				'expectedKey' => 'globalblocking-blockedtext-ip',
			],
			'IP range block' => [
				'xff' => false,
				'target' => '1.2.3.4/24',
				'type' => Block::TYPE_RANGE,
				'expectedKey' => 'globalblocking-blockedtext-range',
			],
			'XFF block' => [
				'xff' => true,
				'target' => '1.2.3.4',
				'type' => Block::TYPE_IP,
				'expectedKey' => 'globalblocking-blockedtext-xff',
			],
			'Account block' => [
				'xff' => false,
				'target' => 'Test',
				'type' => Block::TYPE_USER,
				'expectedKey' => 'globalblocking-blockedtext-user',
			],
			'Global autoblock' => [
				'xff' => false,
				'target' => '1.2.3.4',
				'type' => Block::TYPE_AUTO,
				'expectedKey' => 'globalblocking-blockedtext-autoblock',
			],
			'Global autoblock applied via XFF match' => [
				'xff' => true,
				'target' => '1.2.3.4',
				'type' => Block::TYPE_AUTO,
				'expectedKey' => 'globalblocking-blockedtext-autoblock-xff',
			],
		];
	}

	public function testOnGetBlockErrorMessageKeyForNonGlobalBlock() {
		// Call the method under test with a non-GlobalBlock object to ensure it does nothing other than return true.
		$key = 'blockedtext';
		$block = $this->createMock( AbstractBlock::class );
		$this->assertTrue( $this->getGlobalBlockingHooks()->onGetBlockErrorMessageKey( $block, $key ) );
		$this->assertSame( 'blockedtext', $key );
	}

	public function testOnGetLogTypesOnUser() {
		$types = [];
		$this->assertTrue( $this->getGlobalBlockingHooks()->onGetLogTypesOnUser( $types ) );
		$this->assertContains( 'gblblock', $types, 'The gblblock log type should be added.' );
	}

	public function testOnGetUserBlockWhenGlobalBlocksDisabled() {
		// Define the config as disabling global blocks, which should cause ::onUserGetBlock to return early.
		$hooks = $this->getGlobalBlockingHooks( [ 'config' => new HashConfig( [ 'ApplyGlobalBlocks' => false ] ) ] );
		// Call the method under test. The test verifies that no lookups occur because this is not a database test
		// and trying to interact with the database will cause an exception (thus causing the test to fail).
		$block = null;
		$this->assertTrue(
			$hooks->onGetUserBlock( $this->createMock( User::class ), '1.2.3.4', $block ),
			'::onGetUserBlock should always return true.'
		);
		$this->assertNull( $block, 'The block should not be modified if global blocks are disabled.' );
	}

	public function testOnSpreadAnyEditBlockWhenGlobalBlockNoFound() {
		// Create a mock GlobalBlockLookup service that will always return null from ::getUserBlock
		$mockUser = $this->createMock( User::class );
		$mockGlobalBlockLookup = $this->createMock( GlobalBlockLookup::class );
		$mockGlobalBlockLookup->method( 'getUserBlock' )
			->with( $mockUser, null )
			->willReturn( null );
		// Call the method under test with the mock GlobalBlockLookup service being used.
		$hooks = $this->getGlobalBlockingHooks( [ 'globalBlockLookup' => $mockGlobalBlockLookup ] );
		$blockWasSpread = false;
		$hooks->onSpreadAnyEditBlock( $mockUser, $blockWasSpread );
		// Check that the hook handler did not say that the block was spread, as it should have caused an autoblock
		// for that to occur.
		$this->assertFalse( $blockWasSpread );
	}

	public function testOnSpreadAnyEditBlockWhenGlobalBlockDoesNotCauseAutoblocks() {
		// Create a mock GlobalBlock instance which will always say that the global block does not cause autoblocks.
		$mockGlobalBlock = $this->createMock( GlobalBlock::class );
		$mockGlobalBlock->method( 'isAutoblocking' )
			->willReturn( false );
		// Create a mock GlobalBlockLookup service that will always return the mock GlobalBlock from ::getUserBlock
		$mockUser = $this->createMock( User::class );
		$mockGlobalBlockLookup = $this->createMock( GlobalBlockLookup::class );
		$mockGlobalBlockLookup->method( 'getUserBlock' )
			->with( $mockUser, null )
			->willReturn( null );
		// Call the method under test with the mock GlobalBlockLookup service being used.
		$hooks = $this->getGlobalBlockingHooks( [ 'globalBlockLookup' => $mockGlobalBlockLookup ] );
		$blockWasSpread = false;
		$hooks->onSpreadAnyEditBlock( $mockUser, $blockWasSpread );
		// Check that the hook handler did not say that the block was spread, as it should not autoblock if the
		// global block found does not cause global autoblocks.
		$this->assertFalse( $blockWasSpread );
	}
}
