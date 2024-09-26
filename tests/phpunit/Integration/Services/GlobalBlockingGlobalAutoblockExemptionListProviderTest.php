<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider
 * @group Database
 */
class GlobalBlockingGlobalAutoblockExemptionListProviderTest extends MediaWikiIntegrationTestCase {

	public function testGetExemptIPAddressesWhenNoCentralWiki() {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', false );
		$this->overrideConfigValue( MainConfigNames::UseDatabaseMessages, true );
		// Initially have two IP addresses be listed in the list of exempt IP addresses.
		$this->editPage(
			Title::newFromText( 'globalblocking-globalautoblock-exemptionlist', NS_MEDIAWIKI ),
			"# List of exempt IPs\n* 1.2.3.4\n* 3.4.5.6/24\n"
		);
		// Check that the service was able to parse the list of IP addresses
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$this->assertSame( [ '1.2.3.4', '3.4.5.6/24' ], $objectUnderTest->getExemptIPAddresses() );
		// Edit the message again to remove one of the IP addresses
		$this->editPage(
			Title::newFromText( 'globalblocking-globalautoblock-exemptionlist', NS_MEDIAWIKI ),
			"# List of exempt IPs\n* 1.2.3.4"
		);
		// Check that the service still has the cached version of the list
		$this->assertSame( [ '1.2.3.4', '3.4.5.6/24' ], $objectUnderTest->getExemptIPAddresses() );
		// Clear the cache, then clear the process caching to allow testing, and then check the list
		// is now in-sync with the message override.
		$objectUnderTest->clearCache();
		$this->getServiceContainer()->getMainWANObjectCache()->clearProcessCache();
		$this->assertSame( [ '1.2.3.4' ], $objectUnderTest->getExemptIPAddresses() );
	}
}
