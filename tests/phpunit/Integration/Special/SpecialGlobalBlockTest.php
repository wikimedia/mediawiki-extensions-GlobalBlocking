<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Special;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\SpecialPage\FormSpecialPageTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlock
 * @group Database
 */
class SpecialGlobalBlockTest extends FormSpecialPageTestCase {

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->setMwGlobals( 'wgCentralIdLookupProvider', 'local' );
	}

	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'GlobalBlock' );
	}

	/** @dataProvider provideSetParameter */
	public function testSetParameter( $providedTarget, $fromSubpage, $expectedTarget ) {
		if ( !$fromSubpage ) {
			$mockRequest = new FauxRequest( [ 'wpAddress' => $providedTarget ], true );
			RequestContext::getMain()->setRequest( $mockRequest );
		}
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( $fromSubpage ? $providedTarget : '' );
		$this->assertSame( $expectedTarget, $specialGlobalBlock->target );
	}

	public static function provideSetParameter() {
		return [
			'Empty target from request' => [ '', false, '' ],
			'Empty target from subpage' => [ '', true, '' ],
			'IP target from request' => [ '127.0.0.1', false, '127.0.0.1' ],
			'IP target from subpage' => [ '127.0.0.1', true, '127.0.0.1' ],
			'IP range from subpage' => [ '1.2.3.4/24', true, '1.2.3.0/24' ],
			'User from request' => [ 'testing_test', false, 'Testing test' ],
		];
	}

	public function testLoadExistingBlockWithExistingBlock() {
		$this->overrideConfigValue( 'GlobalBlockingAllowGlobalAccountBlocks', true );
		// Perform a block on 127.0.0.1 so that we can test the loadExistingBlock method returning
		// data on an existing block.
		$testTarget = $this->getTestUser()->getUser();
		GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager()->block(
			$testTarget->getName(),
			'test',
			'infinite',
			$this->getTestUser( [ 'steward' ] )->getUser()
		);
		// Set the target to the user which was blocked.
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( $testTarget->getName() );
		// Call the loadExistingBlock method
		$this->assertArrayEquals(
			[
				'anononly' => 0,
				'reason' => 'test',
				'expiry' => 'indefinite',
			],
			$specialGlobalBlock->loadExistingBlock(),
			false,
			true
		);
	}

	/** @dataProvider provideTargetsWhichAreNotBlocked */
	public function testLoadExistingBlockWithNoBlock( $username ) {
		// Set the target to 127.0.0.1, which is not blocked.
		$specialGlobalBlock = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$specialGlobalBlock->setParameter( $username );
		// Call the loadExistingBlock method
		$this->assertArrayEquals( [], $specialGlobalBlock->loadExistingBlock() );
	}

	public static function provideTargetsWhichAreNotBlocked() {
		return [
			'IP address' => [ '127.0.0.1' ],
			'Non-existent user' => [ 'Non-existent test user1234' ],
		];
	}
}
