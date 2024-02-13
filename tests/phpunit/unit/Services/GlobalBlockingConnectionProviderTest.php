<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Services;

use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWikiUnitTestCase;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider
 */
class GlobalBlockingConnectionProviderTest extends MediaWikiUnitTestCase {
	public function testGetPrimaryGlobalBlockingDatabase() {
		$connectionProvider = $this->createMock( IConnectionProvider::class );
		$connectionProvider->method( 'getPrimaryDatabase' )
			->with( 'virtual-globalblocking' )
			->willReturn( $this->createMock( IDatabase::class ) );
		$provider = new GlobalBlockingConnectionProvider( $connectionProvider );
		$this->assertInstanceOf( IDatabase::class, $provider->getPrimaryGlobalBlockingDatabase() );
	}

	public function testGetReplicaGlobalBlockingDatabase() {
		$connectionProvider = $this->createMock( IConnectionProvider::class );
		$connectionProvider->method( 'getReplicaDatabase' )
			->with( 'virtual-globalblocking' )
			->willReturn( $this->createMock( IReadableDatabase::class ) );
		$provider = new GlobalBlockingConnectionProvider( $connectionProvider );
		$this->assertInstanceOf( IReadableDatabase::class, $provider->getReplicaGlobalBlockingDatabase() );
	}
}
