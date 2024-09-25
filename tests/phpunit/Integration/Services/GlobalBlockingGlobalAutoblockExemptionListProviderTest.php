<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration\Services;

use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Json\FormatJson;
use MediaWiki\MainConfigNames;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;
use MediaWikiIntegrationTestCase;
use MockHttpTrait;
use MWHttpRequest;
use Psr\Log\LoggerInterface;
use StatusValue;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider
 * @group Database
 */
class GlobalBlockingGlobalAutoblockExemptionListProviderTest extends MediaWikiIntegrationTestCase {

	use MockHttpTrait;

	protected function setUp(): void {
		parent::setUp();
		// Add the current site to the SiteStore so that we can get a URL for the site.
		$sitesTable = $this->getServiceContainer()->getSiteStore();
		$site = $sitesTable->getSite( WikiMap::getCurrentWikiId() ) ?? new MediaWikiSite();
		$site->setGlobalId( WikiMap::getCurrentWikiId() );
		// We need to set a page path, otherwise this is not considered a valid site. Use enwiki's path as a mock value.
		$site->setPath( MediaWikiSite::PATH_PAGE, "https://en.wikipedia.org/wiki/$1" );
		$site->setPath( MediaWikiSite::PATH_FILE, "https://en.wikipedia.org/w/$1" );
		$sitesTable->saveSite( $site );
	}

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

	public function testGetExemptIPAddressesWhenListOnCentralWiki() {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', WikiMap::getCurrentWikiId() );
		$this->overrideConfigValue( MainConfigNames::UseDatabaseMessages, true );
		// Set the "local" message to be something different, so that we know that the code used the API request
		// instead of choosing the local message.
		$this->editPage(
			Title::newFromText( 'globalblocking-globalautoblock-exemptionlist', NS_MEDIAWIKI ),
			"# List of exempt IPs\n* 1.2.3.4\n* 3.4.5.6/24\n"
		);
		// Respond with a mock response for the "revisions" query API. We test that the correct URL is used in
		// ::testGetForeignAPIQueryUrl.
		$this->installMockHttp( $this->makeFakeHttpRequest(
			FormatJson::encode( [
				'query' => [ 'pages' => [ [
					'title' => 'MediaWiki:Globalblocking-globalautoblock-exemptionlist',
					'revisions' => [ [ 'slots' => [ 'main' => [ 'content' => "* 1.2.3.4\ntest" ] ] ] ],
				] ] ],
			] )
		) );
		// Check that the service was able to parse the list of IP addresses
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$this->assertSame( [ '1.2.3.4' ], $objectUnderTest->getExemptIPAddresses() );
	}

	public function testGetExemptIPAddressesWhenListOnCentralWikiWhenNoRevisions() {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', WikiMap::getCurrentWikiId() );
		$this->overrideConfigValue( MainConfigNames::UseDatabaseMessages, true );
		// Set the "local" message to be something different, so that we know that the code used the API request
		// instead of choosing the local message.
		$this->editPage(
			Title::newFromText( 'globalblocking-globalautoblock-exemptionlist', NS_MEDIAWIKI ),
			"# List of exempt IPs\n* 1.2.3.4\n* 3.4.5.6/24\n"
		);
		// Respond with a mock response for the "revisions" query API. We test that the correct URL is used in
		// ::testGetForeignAPIQueryUrl.
		$this->installMockHttp( $this->makeFakeHttpRequest(
			FormatJson::encode( [
				'query' => [ 'pages' => [ [
					'title' => 'MediaWiki:Globalblocking-globalautoblock-exemptionlist',
					'missing' => true,
					'known' => true,
				] ] ],
			] )
		) );
		// Check that the service was able to parse the list of IP addresses
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$this->assertSame( [], $objectUnderTest->getExemptIPAddresses() );
	}

	public function testGetExemptIPAddressesWhenListOnCentralWikiOn404() {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', WikiMap::getCurrentWikiId() );
		$this->overrideConfigValue( MainConfigNames::UseDatabaseMessages, true );
		// Set the "local" message to be something, as it should fall back to the local message on the API failure.
		$this->editPage(
			Title::newFromText( 'globalblocking-globalautoblock-exemptionlist', NS_MEDIAWIKI ),
			"# List of exempt IPs\n* 1.2.3.4\n* 3.4.5.6/24\n"
		);
		// Respond with a 404 to indicate a failure in the request.
		$this->installMockHttp( $this->makeFakeHttpRequest(
			FormatJson::encode( [] ),
			404
		) );
		// Check that the exempt list is empty and that an error was logged.
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'error' );
		$this->setLogger( 'GlobalBlocking', $mockLogger );
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$this->assertSame( [ '1.2.3.4', '3.4.5.6/24' ], $objectUnderTest->getExemptIPAddresses() );
	}

	public function testGetExemptIPAddressesWhenListOnCentralWikiOnWarning() {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', WikiMap::getCurrentWikiId() );
		// Respond with a content but have a warning with it too.
		$mwHttpRequest = $this->createMock( MWHttpRequest::class );
		$status = new StatusValue();
		$status->warning( 'test' );
		$mwHttpRequest->method( 'execute' )
			->willReturn( $status );
		$mwHttpRequest->method( 'getContent' )
			->willReturn( FormatJson::encode( [
				'query' => [ 'pages' => [ [
					'title' => 'MediaWiki:Globalblocking-globalautoblock-exemptionlist',
					'revisions' => [ [ 'slots' => [ 'main' => [ 'content' => "abc\n* 1.2.3.4/23\ntest\n" ] ] ] ],
				] ] ],
			] ) );
		$this->installMockHttp( $mwHttpRequest );
		// Check that the exempt list is correct and that a warning was logged.
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'warning' );
		$this->setLogger( 'GlobalBlocking', $mockLogger );
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$this->assertSame( [ '1.2.3.4/23' ], $objectUnderTest->getExemptIPAddresses() );
	}

	public function testGetForeignAPIQueryUrl() {
		// Get a URL for the API
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', WikiMap::getCurrentWikiId() );
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$objectUnderTest = TestingAccessWrapper::newFromObject( $objectUnderTest );
		// Check that the URL for the API is as expected.
		$actualUrl = $objectUnderTest->getForeignAPIQueryUrl();
		$actualUrlParts = $this->getServiceContainer()->getUrlUtils()->parse( $actualUrl );
		$this->assertSame( 'en.wikipedia.org', $actualUrlParts['host'] );
		$this->assertSame( '/w/api.php', $actualUrlParts['path'] );
		$this->assertArrayEquals(
			[
				'formatversion' => '2', 'format' => 'json', 'rvslots' => 'main', 'rvprop' => 'content',
				'prop' => 'revisions', 'titles' => 'MediaWiki:globalblocking-globalautoblock-exemptionlist',
				'action' => 'query', 'rvlimit' => 1,
			],
			wfCgiToArray( $actualUrlParts['query'] ),
			false, true
		);
	}

	public function testGetForeignAPIQueryUrlForUndefinedSite() {
		$this->overrideConfigValue( 'GlobalBlockingCentralWiki', 'undefinedsite1234' );
		$objectUnderTest = GlobalBlockingServices::wrap( $this->getServiceContainer() )
			->getGlobalAutoblockExemptionListProvider();
		$objectUnderTest = TestingAccessWrapper::newFromObject( $objectUnderTest );
		$this->assertFalse( $objectUnderTest->getForeignAPIQueryUrl() );
	}
}
