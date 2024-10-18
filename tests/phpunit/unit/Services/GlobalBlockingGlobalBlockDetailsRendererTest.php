<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Services;

use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer
 */
class GlobalBlockingGlobalBlockDetailsRendererTest extends MediaWikiUnitTestCase {

	use MockServiceDependenciesTrait;

	public function testGetTargetUsernameForAutoblock() {
		$objectUnderTest = $this->newServiceInstance( GlobalBlockingGlobalBlockDetailsRenderer::class, [] );
		$this->assertArrayEquals(
			[ '', false ],
			$objectUnderTest->getTargetUsername(
				(object)[ 'gb_autoblock_parent_id' => 3 ],
				$this->createMock( IContextSource::class )
			),
			true
		);
	}
}
