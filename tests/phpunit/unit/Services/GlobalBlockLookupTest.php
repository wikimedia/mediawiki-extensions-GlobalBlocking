<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Services;

use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Language\RawMessage;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use MediaWiki\User\User;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup
 */
class GlobalBlockLookupTest extends MediaWikiUnitTestCase {

	use MockServiceDependenciesTrait;

	public function testGetUserBlockErrorsWhenNoBlockAndCacheMatch() {
		$mockMessage = $this->createMock( RawMessage::class );
		$objectUnderTest = $this->getMockBuilder( GlobalBlockLookup::class )
			->onlyMethods( [ 'getUserBlockDetailsCacheResult' ] )
			->disableOriginalConstructor()
			->getMock();
		$objectUnderTest->method( 'getUserBlockDetailsCacheResult' )
			->willReturn( [
				'error' => [ $mockMessage ],
				'block' => (object)[],
			] );
		$this->assertArrayEquals(
			[ $mockMessage ],
			$objectUnderTest->getUserBlockErrors( $this->createMock( User::class ), null ),
			true,
			true,
			'::getUserBlockErrors should have returned the cached result'
		);
	}
}
