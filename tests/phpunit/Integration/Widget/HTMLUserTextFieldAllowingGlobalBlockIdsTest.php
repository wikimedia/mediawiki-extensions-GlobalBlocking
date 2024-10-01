<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Widget;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\Widget\HTMLUserTextFieldAllowingGlobalBlockIds;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWikiIntegrationTestCase;
use Wikimedia\Message\MessageSpecifier;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Widget\HTMLUserTextFieldAllowingGlobalBlockIds
 * @group Database
 */
class HTMLUserTextFieldAllowingGlobalBlockIdsTest extends MediaWikiIntegrationTestCase {
	public function testValidateForNonExistingUser() {
		$objectUnderTest = new HTMLUserTextFieldAllowingGlobalBlockIds( [
			'parent' => HTMLForm::factory( 'ooui', [], RequestContext::getMain() ),
			'name' => 'address',
			'exists' => true,
		] );
		$actualValidateReturnValue = $objectUnderTest->validate( 'NonExistingTestUser1234', [] );
		$this->assertInstanceOf( MessageSpecifier::class, $actualValidateReturnValue );
		$this->assertSame( 'htmlform-user-not-exists', $actualValidateReturnValue->getKey() );
	}
}
