<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingBlockPurger;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockReasonFormatter;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices
 */
class GlobalBlockingServicesTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider provideGetters */
	public function testGetters( string $method, string $expectedClass ) {
		$this->assertInstanceOf(
			$expectedClass,
			GlobalBlockingServices::wrap( MediaWikiServices::getInstance() )->$method()
		);
	}

	public static function provideGetters() {
		return [
			'::getReasonFormatter' => [ 'getReasonFormatter', GlobalBlockReasonFormatter::class ],
			'::getGlobalBlockingBlockPurger' => [ 'getGlobalBlockingBlockPurger', GlobalBlockingBlockPurger::class ],
			'::getGlobalBlockingConnectionProvider' => [
				'getGlobalBlockingConnectionProvider', GlobalBlockingConnectionProvider::class
			],
			'::getGlobalBlockLocalStatusLookup' => [
				'getGlobalBlockLocalStatusLookup', GlobalBlockLocalStatusLookup::class
			],
			'::getGlobalBlockLocalStatusManager' => [
				'getGlobalBlockLocalStatusManager', GlobalBlockLocalStatusManager::class
			],
			'::getGlobalBlockLookup' => [ 'getGlobalBlockLookup', GlobalBlockLookup::class ],
			'::getGlobalBlockManager' => [ 'getGlobalBlockManager', GlobalBlockManager::class ],
			'::getGlobalBlockingLinkBuilder' => [ 'getGlobalBlockingLinkBuilder', GlobalBlockingLinkBuilder::class ],
		];
	}
}
