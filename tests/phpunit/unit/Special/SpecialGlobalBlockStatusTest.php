<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Special;

use MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockStatus;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockStatus
 */
class SpecialGlobalBlockStatusTest extends MediaWikiUnitTestCase {

	use MockServiceDependenciesTrait;

	public function testDoesWrites() {
		$objectUnderTest = $this->newServiceInstance( SpecialGlobalBlockStatus::class, [] );
		$this->assertTrue(
			$objectUnderTest->doesWrites(), '::doesWrites must return true as this special page causes DB writes'
		);
	}
}
