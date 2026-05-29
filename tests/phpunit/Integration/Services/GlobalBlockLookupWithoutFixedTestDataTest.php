<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup
 * @group Database
 */
class GlobalBlockLookupWithoutFixedTestDataTest extends MediaWikiIntegrationTestCase {

	use TempUserTestTrait;
	use MockAuthorityTrait;

	protected function setUp(): void {
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	public function testGetUserBlockResultIsCached(): void {
		$user = $this->getTestUser()->getUser();

		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockStatus = $globalBlockingServices->getGlobalBlockManager()->block(
			$user->getName(),
			'test',
			'indefinite',
			$this->getTestSysop()->getAuthority()
		);
		$this->assertStatusGood( $globalBlockStatus );
		$globalBlockId = $globalBlockStatus->getValue()['id'];

		// The first call should indicate the user is blocked
		$globalBlockLookup = $globalBlockingServices->getGlobalBlockLookup();
		$firstResult = $globalBlockLookup->getUserBlock( $user, null );
		$this->assertNotNull( $firstResult );
		$this->assertSame( $globalBlockId, $firstResult->getId() );

		// Drop the block from the DB to simulate it expiring or otherwise being removed mid-request
		$this->getDb()->newDeleteQueryBuilder()
			->deleteFrom( 'globalblocks' )
			->where( [ 'gb_id' => $globalBlockId ] )
			->caller( __METHOD__ )
			->execute();

		// The second call should return the cached result, even though the block no longer exists
		$secondResult = $globalBlockLookup->getUserBlock( $user, null );
		$this->assertSame( $globalBlockId, $secondResult->getId() );
	}
}
