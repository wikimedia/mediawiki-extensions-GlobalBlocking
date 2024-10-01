<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Widget;

use MediaWiki\Extension\GlobalBlocking\Widget\HTMLUserTextFieldAllowingGlobalBlockIds;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Widget\HTMLUserTextFieldAllowingGlobalBlockIds
 */
class HTMLUserTextFieldAllowingGlobalBlockIdsTest extends MediaWikiUnitTestCase {
	/** @dataProvider provideValidateForBlockId */
	public function testValidateForBlockId( $value ) {
		$objectUnderTest = new HTMLUserTextFieldAllowingGlobalBlockIds( [
			'parent' => $this->createMock( HTMLForm::class ),
			'name' => 'address',
		] );
		$this->assertTrue( $objectUnderTest->validate( $value, [] ) );
	}

	public static function provideValidateForBlockId() {
		return [
			'Block ID with one digit' => [ '#1' ],
			'Block ID with several digits' => [ '#123455' ],
		];
	}
}
