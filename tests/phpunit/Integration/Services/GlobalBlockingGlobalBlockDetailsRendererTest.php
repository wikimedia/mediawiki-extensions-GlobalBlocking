<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer
 * @group Database
 */
class GlobalBlockingGlobalBlockDetailsRendererTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider provideGetBlockOptionsForDisplay */
	public function testGetBlockOptionsForDisplay( $row, $expectedReturnArray ) {
		$this->setUserLang( 'qqx' );
		$this->assertArrayEquals(
			$expectedReturnArray,
			GlobalBlockingServices::wrap( $this->getServiceContainer() )
				->getGlobalBlockDetailsRenderer()
				->getBlockOptionsForDisplay( (object)$row, RequestContext::getMain() )
		);
	}

	public static function provideGetBlockOptionsForDisplay() {
		return [
			'Global account block does disables autoblocking' => [
				[
					'gb_id' => 1, 'gb_anon_only' => 0, 'gb_create_account' => 0, 'gb_enable_autoblock' => 0,
					'gb_target_central_id' => 1, 'gb_block_email' => 0,
				],
				[ '(globalblocking-block-flag-autoblock-disabled)' ],
			],
			'Global IP block set to anon only and disables account creation' => [
				[
					'gb_id' => 1, 'gb_anon_only' => 1, 'gb_create_account' => 1, 'gb_enable_autoblock' => 0,
					'gb_target_central_id' => 0, 'gb_block_email' => 0,
				],
				[
					'(globalblocking-list-anononly)',
					'(globalblocking-block-flag-account-creation-disabled)',
				],
			],
			'Global block prevents emailing other users' => [
				[
					'gb_id' => 1, 'gb_anon_only' => 0, 'gb_create_account' => 0, 'gb_enable_autoblock' => 1,
					'gb_target_central_id' => 1, 'gb_block_email' => 1,
				],
				[ '(globalblocking-block-flag-email-blocked)' ],
			],
		];
	}
}
