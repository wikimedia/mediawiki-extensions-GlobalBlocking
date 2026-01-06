<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Maintenance;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Maintenance\GloballyBlock;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use MediaWiki\User\UserFactory;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Maintenance\GloballyBlock
 * @group Database
 */
class GloballyBlockTest extends MaintenanceBaseTestCase {

	protected function setUp(): void {
		parent::setUp();
		// We don't want to test specifically the CentralAuth implementation of the CentralIdLookup. As such, force it
		// to be the local provider.
		$this->overrideConfigValue( MainConfigNames::CentralIdLookupProvider, 'local' );
	}

	protected function getMaintenanceClass() {
		return GloballyBlock::class;
	}

	public function testExecuteForInvalidPerformer() {
		$this->maintenance->setOption( 'performer', ':::' );
		$this->expectCallToFatalError();
		$this->expectOutputRegex( "/Unable to parse performer's username/" );
		$this->maintenance->execute();
	}

	private function getFileWithContent( string $content ): string {
		$testFilename = $this->getNewTempFile();
		$testFile = fopen( $testFilename, 'w' );
		fwrite( $testFile, $content );
		fclose( $testFile );
		return $testFilename;
	}

	/** @dataProvider provideExecuteForBlock */
	public function testExecuteForBlock(
		string $target, array $options, bool $shouldBeAnonOnly, bool $shouldAllowAccountCreation,
		bool $shouldBlockEmail
	) {
		// Run the maintenance script with the $options and $target user
		$this->maintenance->setArg( 'file', $this->getFileWithContent( $target ) );
		foreach ( $options as $name => $value ) {
			$this->maintenance->setOption( $name, $value );
		}
		$this->expectOutputRegex( "/Globally blocking '" . preg_quote( $target, '/' ) . "' succeeded/" );
		$this->maintenance->execute();
		// Check that the $target is actually blocked
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$block = $globalBlockingServices->getGlobalBlockLookup()->getUserBlock(
			$this->getServiceContainer()->getUserFactory()->newFromName( $target, UserFactory::RIGOR_NONE ),
			null
		);
		$this->assertInstanceOf( GlobalBlock::class, $block );
		$this->assertSame( !$shouldBeAnonOnly, $block->isHardblock() );
		$this->assertSame( !$shouldAllowAccountCreation, $block->isCreateAccountBlocked() );
		$this->assertSame( $shouldBlockEmail, $block->isEmailBlocked() );
		// Return the block to allow other tests to perform further assertions.
		return $block;
	}

	public static function provideExecuteForBlock() {
		return [
			'Blocking an IP range' => [
				'target' => '1.2.3.4/24',
				'options' => [],
				'shouldBeAnonOnly' => false,
				'shouldAllowAccountCreation' => false,
				'shouldBlockEmail' => false,
			],
			'Blocking an IP address with hard block disabled and account creation enabled' => [
				'target' => '1.2.3.4',
				'options' => [ 'disable-hardblock' => 1, 'allow-createaccount' => 1 ],
				'shouldBeAnonOnly' => true,
				'shouldAllowAccountCreation' => true,
				'shouldBlockEmail' => false,
			],
		];
	}

	public function testExecuteForUserBlock() {
		ConvertibleTimestamp::setFakeTime( '20240506070809' );
		$targetUser = $this->getTestUser()->getUserIdentity();
		$testPerformer = $this->getTestUser( [ 'steward' ] )->getUserIdentity();
		// Run the maintenance script
		$block = $this->testExecuteForBlock(
			$targetUser,
			[
				'performer' => $testPerformer->getName(), 'expiry' => '1 week',
				'reason' => 'abc', 'reblock' => 1, 'block-email' => true,
			],
			false, false, true
		);
		$this->assertTrue( $testPerformer->equals( $block->getBlocker() ) );
		$this->assertSame( '20240513070809', $block->getExpiry() );
		$this->assertSame( 'abc', $block->getReasonComment()->text );
	}

	public function testExecuteForUnblock() {
		// Block the IP to set up the test
		$globalBlockingServices = GlobalBlockingServices::wrap( $this->getServiceContainer() );
		$globalBlockingServices->getGlobalBlockManager()
			->block( '1.2.3.4', '', 'indefinite', $this->getTestUser( [ 'steward' ] )->getUserIdentity() );
		$block = $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockingBlock( '1.2.3.4', 0 );
		$this->assertNotNull( $block );
		// Run the maintenance script
		$this->maintenance->setArg( 'file', $this->getFileWithContent( "\n\n1.2.3.4\n\n" ) );
		$this->maintenance->setOption( 'unblock', 1 );
		$this->expectOutputRegex( "/Globally unblocking '1.2.3.4' succeeded/" );
		$this->maintenance->execute();
		// Check that the $target is actually unblocked
		$block = $globalBlockingServices->getGlobalBlockLookup()->getGlobalBlockingBlock( '1.2.3.4', 0 );
		$this->assertNull( $block );
	}

	public function testExecuteForUnblockWhenNotBlocked() {
		// Run the maintenance script
		$this->maintenance->setArg( 'file', $this->getFileWithContent( "\n\n1.2.3.4\n\n" ) );
		$this->maintenance->setOption( 'unblock', 1 );
		$this->expectOutputRegex( "/Globally unblocking '1.2.3.4' failed.*is not globally blocked/" );
		$this->maintenance->execute();
	}
}
