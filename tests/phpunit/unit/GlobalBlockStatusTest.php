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
		$this->assertSame( 'value', $status->getValue() );
		$this->assertTrue( $status->isGlobalBlockOK() );
		$this->assertFalse( $status->isLocalBlockOK() );
		$this->assertFalse( $status->hasLocalBlockError() );
		$this->assertTrue( $status->getGlobalStatus()->isOK() );
	}

	public function testNewFatal() {
		$status = GlobalBlockStatus::newFatal( 'some-message' );
		$this->assertInstanceOf( GlobalBlockStatus::class, $status );
		$this->assertSame( 'some-message', $status->getMessages()[0]->getKey() );
		$this->assertFalse( $status->isOK() );
		$this->assertFalse( $status->isGlobalBlockOK() );
		$this->assertFalse( $status->isLocalBlockOK() );
		$this->assertFalse( $status->hasLocalBlockError() );
		$this->assertFalse( $status->getGlobalStatus()->isOK() );
	}

	public function testWithLocalStatusFatal() {
		$status = GlobalBlockStatus::newGood( 'value' );
		$status = $status->withLocalStatus( StatusValue::newFatal( 'some-message' ) );
		$this->assertFalse( $status->isOK() );
		$this->assertTrue( $status->isGlobalBlockOK() );
		$this->assertFalse( $status->isLocalBlockOK() );
		$this->assertTrue( $status->hasLocalBlockError() );
		$this->assertSame( 'some-message', $status->getMessages()[0]->getKey() );
		$this->assertSame( 'value', $status->getValue() );
		$this->assertTrue( $status->getGlobalStatus()->isOK() );
	}

	public function testWithLocalStatusGood() {
		$status = GlobalBlockStatus::newGood( 'value' );
		$status = $status->withLocalStatus( StatusValue::newGood( 'local-value' ) );
		$this->assertTrue( $status->isOK() );
		$this->assertTrue( $status->isGlobalBlockOK() );
		$this->assertTrue( $status->isLocalBlockOK() );
		$this->assertFalse( $status->hasLocalBlockError() );
		$this->assertSame( 'value', $status->getValue() );
		$this->assertTrue( $status->getGlobalStatus()->isOK() );
	}
}
