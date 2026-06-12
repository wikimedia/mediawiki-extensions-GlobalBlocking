<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Maintenance;

use MediaWiki\Extension\GlobalBlocking\Maintenance\UpdateAutoBlockParentIdColumn;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use RuntimeException;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Maintenance\UpdateAutoBlockParentIdColumn
 * @group Database
 */
class UpdateAutoBlockParentIdColumnWithoutSchemaChangeTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return UpdateAutoBlockParentIdColumn::class;
	}

	public function testExecuteWhenColumnNotNullable(): void {
		$this->maintenance->execute();
		$this->expectOutputRegex( '/The field globalblocks.gb_autoblock_parent_id is not nullable, nothing to do/' );
	}

	public function testExecuteWhenDatabaseConnectionNotMaintainable(): void {
		$mockConnectionProvider = $this->createMock( IConnectionProvider::class );
		$mockConnectionProvider->method( 'getReplicaDatabase' )
			->willReturn( $this->createMock( IReadableDatabase::class ) );
		$this->setService( 'ConnectionProvider', $mockConnectionProvider );

		$this->expectException( RuntimeException::class );
		$this->maintenance->execute();
	}
}
