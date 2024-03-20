<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Specials;

use FauxRequest;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockList;
use SpecialPageTestBase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\GlobalBlocking\Special\SpecialGlobalBlockList
 */
class SpecialGlobalBlockListTest extends SpecialPageTestBase {
	/**
	 * @inheritDoc
	 */
	protected function newSpecialPage() {
		$services = $this->getServiceContainer();
		return new SpecialGlobalBlockList(
			$services->getBlockUtils(),
			$services->getCommentFormatter(),
			$services->getCentralIdLookup(),
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockLookup(),
			GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockingLinkBuilder()
		);
	}

	/**
	 * @dataProvider provideIPParam
	 */
	public function testIpParam( string $ip, bool $expectResult ) {
		[ $html ] = $this->executeSpecialPage(
			'',
			new FauxRequest( [
				'ip' => $ip,
			] )
		);
		if ( $expectResult ) {
			$this->assertStringContainsString( '0:0:0:0:0:0:0:0/19', $html );
		} else {
			$this->assertStringNotContainsString( '0:0:0:0:0:0:0:0/19', $html );
		}
	}

	public function provideIPParam() {
		return [
			'single IP' => [
				'::1',
				true
			],
			'exact range' => [
				'::1/19',
				true
			],
			'narrower range' => [
				'::1/20',
				true
			],
			'wider range' => [
				'::1/18',
				false
			]
		];
	}

	public function addDBData() {
		$this->db->newInsertQueryBuilder()
			->insertInto( 'globalblocks' )
			->row( [
				'gb_address' => '0:0:0:0:0:0:0:0/19',
				'gb_by_wiki' => 'mediawiki',
				'gb_reason' => '',
				'gb_timestamp' => '20000101000000',
				'gb_expiry' => 'infinity',
				'gb_range_start' => 'v6-00000000000000000000000000000000',
				'gb_range_end' => 'v6-00001FFFFFFFFFFFFFFFFFFFFFFFFFFF',
				'gb_by_central_id' => 1,
			] )
			->execute();
	}
}
