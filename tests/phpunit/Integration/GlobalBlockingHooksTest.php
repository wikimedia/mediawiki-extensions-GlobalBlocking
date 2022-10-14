<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use Config;
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
			$this->createMock( Config::class )
		);
	}

	public function testConstructor() {
		$hooks = $this->getGlobalBlockingHooks();
		$this->assertInstanceOf( GlobalBlockingHooks::class, $hooks );
	}
}
