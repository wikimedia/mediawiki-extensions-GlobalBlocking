<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Site\SiteLookup;
use MediaWiki\Status\StatusFormatter;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider
 */
class GlobalBlockingGlobalAutoblockExemptionListProviderTest extends MediaWikiUnitTestCase {

	/** @dataProvider provideIsExempt */
	public function testIsExempt( $exemptIPs, $ip, $expectedReturnValue ) {
		$mockObject = $this->getMockBuilder( GlobalBlockingGlobalAutoblockExemptionListProvider::class )
			->onlyMethods( [ 'getExemptIPAddresses' ] )
			->setConstructorArgs( [
				$this->createMock( ServiceOptions::class ),
				$this->createMock( ITextFormatter::class ),
				$this->createMock( WANObjectCache::class ),
				$this->createMock( HttpRequestFactory::class ),
				$this->createMock( SiteLookup::class ),
				$this->createMock( StatusFormatter::class ),
				$this->createMock( LoggerInterface::class ),
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
