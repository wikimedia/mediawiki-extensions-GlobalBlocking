<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Services;

use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider;
use MediaWikiUnitTestCase;
use WANObjectCache;
use Wikimedia\Message\ITextFormatter;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider
 */
class GlobalBlockingGlobalAutoblockExemptionListProviderTest extends MediaWikiUnitTestCase {

	/** @dataProvider provideIsExempt */
	public function testIsExempt( $exemptIPs, $ip, $expectedReturnValue ) {
		$mockObject = $this->getMockBuilder( GlobalBlockingGlobalAutoblockExemptionListProvider::class )
			->onlyMethods( [ 'getExemptIPAddresses' ] )
			->setConstructorArgs( [
				$this->createMock( ITextFormatter::class ),
				$this->createMock( WANObjectCache::class ),
			] )
			->getMock();
		$mockObject->method( 'getExemptIPAddresses' )
			->willReturn( $exemptIPs );
		$this->assertSame( $expectedReturnValue, $mockObject->isExempt( $ip ) );
	}

	public static function provideIsExempt() {
		return [
			'IP exemption list is empty' => [ [], '1.2.3.4', false ],
			'IP is not exempt' => [ [ '4.3.2.1', '5.6.7.8/24' ], '1.2.3.4', false ],
			'IP is exempt' => [ [ '4.3.2.1', '5.6.7.8/24' ], '4.3.2.1', true ],
			'IP is in exempt range' => [ [ '4.3.2.1', '5.6.7.8/24' ], '5.6.7.5', true ],
		];
	}
}
