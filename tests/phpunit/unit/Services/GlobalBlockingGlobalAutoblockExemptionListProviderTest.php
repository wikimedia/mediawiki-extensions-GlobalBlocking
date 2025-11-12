<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Site\SiteLookup;
use MediaWiki\Status\StatusFormatter;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider
 */
class GlobalBlockingGlobalAutoblockExemptionListProviderTest extends MediaWikiUnitTestCase {

	private function getProvider(
		array $configExemptIPs,
		array $onWikiExemptIPs,
	): GlobalBlockingGlobalAutoblockExemptionListProvider {
		$mockObject = $this->getMockBuilder( GlobalBlockingGlobalAutoblockExemptionListProvider::class )
			->onlyMethods( [ 'getOnWikiExemptIPAddresses' ] )
			->setConstructorArgs( [
				new ServiceOptions(
					GlobalBlockingGlobalAutoblockExemptionListProvider::CONSTRUCTOR_OPTIONS,
					[
						'GlobalBlockingCentralWiki' => null,
						'GlobalBlockingAutoblockExemptions' => $configExemptIPs,
					]
				),
				$this->createMock( ITextFormatter::class ),
				$this->createMock( WANObjectCache::class ),
				$this->createMock( HttpRequestFactory::class ),
				$this->createMock( SiteLookup::class ),
				$this->createMock( StatusFormatter::class ),
				new NullLogger(),
			] )
			->getMock();
		$mockObject->method( 'getOnWikiExemptIPAddresses' )
			->willReturn( $onWikiExemptIPs );
		return $mockObject;
	}

	/** @dataProvider provideIsExempt */
	public function testIsExemptConfig( $exemptIPs, $ip, $expectedReturnValue ) {
		$provider = $this->getProvider( $exemptIPs, [] );
		$this->assertSame( $expectedReturnValue, $provider->isExempt( $ip ) );
	}

	/** @dataProvider provideIsExempt */
	public function testIsExemptOnWiki( $exemptIPs, $ip, $expectedReturnValue ) {
		$provider = $this->getProvider( [], $exemptIPs );
		$this->assertSame( $expectedReturnValue, $provider->isExempt( $ip ) );
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
