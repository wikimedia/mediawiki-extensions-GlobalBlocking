<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Provides database connections to the virtual-globalblocking database domain where the
 * globalblocks table is stored.
 *
 * @since 1.42
 */
class GlobalBlockingConnectionProvider {

	public function __construct( private readonly IConnectionProvider $connectionProvider ) {
	}

	public function getPrimaryGlobalBlockingDatabase(): IDatabase {
		return $this->connectionProvider->getPrimaryDatabase( 'virtual-globalblocking' );
	}

	public function getReplicaGlobalBlockingDatabase(): IReadableDatabase {
		return $this->connectionProvider->getReplicaDatabase( 'virtual-globalblocking' );
	}
}
