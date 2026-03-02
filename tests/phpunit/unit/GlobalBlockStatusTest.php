<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockStatus;
use MediaWikiUnitTestCase;
use StatusValue;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockStatus
 */
class GlobalBlockStatusTest extends MediaWikiUnitTestCase {
	public function testNewGood() {
		$status = GlobalBlockStatus::newGood( 'value' );
		$this->assertInstanceOf( GlobalBlockStatus::class, $status );
		$this->assertStatusValue( 'value', $status );
		$this->assertTrue( $status->isGlobalBlockOK() );
		$this->assertFalse( $status->isLocalBlockOK() );
		$this->assertFalse( $status->hasLocalBlockError() );
		$this->assertStatusGood( $status->getGlobalStatus() );
	}

	public function testNewFatal() {
		$status = GlobalBlockStatus::newFatal( 'some-message' );
		$this->assertInstanceOf( GlobalBlockStatus::class, $status );
		$this->assertStatusError( 'some-message', $status );
		$this->assertFalse( $status->isGlobalBlockOK() );
		$this->assertFalse( $status->isLocalBlockOK() );
		$this->assertFalse( $status->hasLocalBlockError() );
		$this->assertStatusNotOK( $status->getGlobalStatus() );
	}

	public function testWithLocalStatusFatal() {
		$status = GlobalBlockStatus::newGood( 'value' );
		$status = $status->withLocalStatus( StatusValue::newFatal( 'some-message' ) );
		$this->assertStatusNotOK( $status );
		$this->assertTrue( $status->isGlobalBlockOK() );
		$this->assertFalse( $status->isLocalBlockOK() );
		$this->assertTrue( $status->hasLocalBlockError() );
		$this->assertStatusError( 'some-message', $status );
		$this->assertStatusValue( 'value', $status );
		$this->assertStatusGood( $status->getGlobalStatus() );
	}

	public function testWithLocalStatusGood() {
		$status = GlobalBlockStatus::newGood( 'value' );
		$status = $status->withLocalStatus( StatusValue::newGood( 'local-value' ) );
		$this->assertStatusGood( $status );
		$this->assertTrue( $status->isGlobalBlockOK() );
		$this->assertTrue( $status->isLocalBlockOK() );
		$this->assertFalse( $status->hasLocalBlockError() );
		$this->assertStatusValue( 'value', $status );
		$this->assertStatusGood( $status->getGlobalStatus() );
	}
}
