<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingExpirySelectorBuilder;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingExpirySelectorBuilder
 * @group Database
 */
class GlobalBlockingExpirySelectorBuilderTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider provideBuildExpirySelector */
	public function testBuildExpirySelector( $globalBlockingMessageText, $coreMessageText, $expectedReturnArray ) {
		// Define overrides for the two messages used to hold the options to be shown.
		$this->overrideConfigValue( MainConfigNames::UseDatabaseMessages, true );
		$this->editPage(
			Title::newFromText( 'globalblocking-expiry-options', NS_MEDIAWIKI ), $globalBlockingMessageText
		);
		$this->editPage( Title::newFromText( 'ipboptions', NS_MEDIAWIKI ), $coreMessageText );
		// Call the method under test and verify that the options returned are as expected.
		$globalBlockingExpirySelectorBuilder = new GlobalBlockingExpirySelectorBuilder();
		$this->assertArrayEquals(
			$expectedReturnArray,
			$globalBlockingExpirySelectorBuilder->buildExpirySelector( RequestContext::getMain() ),
			true,
			true
		);
	}

	public static function provideBuildExpirySelector() {
		return [
			'globalblocking-expiry-options and ipboptions are disabled' => [ '-', '-', [] ],
			'globalblocking-expiry-options is disabled' => [
				'-', '1 hour:1 hour,1 day:1 day,3 days',
				[ '1 hour' => '1 hour', '1 day' => '1 day', '3 days' => '3 days' ],
			],
			'ipboptions is disabled' => [
				'2 hours:2 hours,1 day:1 day,3 days', '-',
				[ '2 hours' => '2 hours', '1 day' => '1 day', '3 days' => '3 days' ],
			],
			'globalblocking-expiry-options and ipboptions are not disabled' => [
				'2 hours,1 day,3 days', '1 hour:1 hour,1 day:1 day,3 days',
				[ '2 hours' => '2 hours', '1 day' => '1 day', '3 days' => '3 days' ],
			],
		];
	}
}
