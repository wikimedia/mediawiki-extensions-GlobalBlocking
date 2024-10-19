<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Json\FormatJson;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikimedia\IPUtils;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\Message\MessageValue;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * Provides access to the list of IP addresses which are exempt from global autoblocks.
 *
 * @since 1.43
 */
class GlobalBlockingGlobalAutoblockExemptionListProvider {
	private const CACHE_VERSION = 0;

	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockingCentralWiki',
	];

	private ServiceOptions $options;
	private ITextFormatter $textFormatter;
	private WANObjectCache $wanObjectCache;
	private HttpRequestFactory $httpRequestFactory;
	private SiteLookup $siteLookup;
	private StatusFormatter $statusFormatter;
	private LoggerInterface $logger;

	public function __construct(
		ServiceOptions $options,
		ITextFormatter $textFormatter,
		WANObjectCache $wanObjectCache,
		HttpRequestFactory $httpRequestFactory,
		SiteLookup $siteLookup,
		StatusFormatter $statusFormatter,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->textFormatter = $textFormatter;
		$this->wanObjectCache = $wanObjectCache;
		$this->httpRequestFactory = $httpRequestFactory;
		$this->siteLookup = $siteLookup;
		$this->statusFormatter = $statusFormatter;
		$this->logger = $logger;
	}

	/**
	 * Checks whether a given IP is on the global autoblock exemption list
	 *
	 * @param string $ip The IP to check
	 * @return bool
	 */
	public function isExempt( string $ip ): bool {
		foreach ( $this->getExemptIPAddresses() as $ipOrRangeToCheck ) {
			if ( IPUtils::isInRange( $ip, $ipOrRangeToCheck ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the list of IP addresses that are exempt from global autoblocks.
	 *
	 * The result of this method is cached to improve performance. Call ::clearCache to clear the cache if the
	 * exempt list has been recently edited and needs a refresh.
	 *
	 * @return array
	 */
	public function getExemptIPAddresses(): array {
		$exemptIPsAndRanges = $this->wanObjectCache->getWithSetCallback(
			$this->getCacheKey(),
			ExpirationAwareness::TTL_HOUR,
			function () {
				return $this->fetchExemptIPAddresses();
			},
			[ 'version' => self::CACHE_VERSION, 'pcTTL' => ExpirationAwareness::TTL_PROC_SHORT ]
		);
		// If we failed to get the list of exempt IP addresses, then try using the local message.
		// This isn't ideal, but is probably better than throwing an exception.
		// Because the cached value is false, the next call to this method will try to fetch the
		// IP addresses again from ::fetchExemptIPAddresses.
		if ( $exemptIPsAndRanges === false ) {
			return $this->parseIPAddressList( $this->textFormatter->format(
				MessageValue::new( 'globalblocking-globalautoblock-exemptionlist' )
			) );
		}
		return $exemptIPsAndRanges;
	}

	/**
	 * Clears the cache of IP addresses exempt from global autoblocks.
	 *
	 * @return void
	 */
	public function clearCache() {
		$this->wanObjectCache->delete( $this->getCacheKey() );
	}

	private function getCacheKey(): string {
		return $this->wanObjectCache->makeGlobalKey(
			'GlobalBlocking',
			'GlobalAutoBlockExemptList',
			$this->options->get( 'GlobalBlockingCentralWiki' )
		);
	}

	/**
	 * Get the URL used to get the content of the global autoblock exempt list on the central wiki.
	 *
	 * @return string|false
	 */
	private function getForeignAPIQueryUrl() {
		$centralWiki = $this->options->get( 'GlobalBlockingCentralWiki' );
		if ( !$centralWiki ) {
			 return false;
		}
		$site = $this->siteLookup->getSite( $centralWiki );
		if ( !( $site instanceof MediaWikiSite ) ) {
			return false;
		}
		$exemptList = Title::makeName( NS_MEDIAWIKI, 'globalblocking-globalautoblock-exemptionlist', '', '', true );
		return wfAppendQuery(
			$site->getFileUrl( 'api.php' ),
			[
				'action' => 'query', 'prop' => 'revisions', 'rvslots' => 'main', 'rvprop' => 'content',
				'formatversion' => 2, 'format' => 'json', 'titles' => $exemptList, 'rvlimit' => 1,
			]
		);
	}

	/**
	 * Parse the globalblocking-globalautoblock-exemptionlist message into an array of exempt IP addresses
	 * and/or ranges.
	 *
	 * @return array
	 */
	private function parseIPAddressList( string $exemptionList ): array {
		$ips = [];
		$exemptionListLines = explode( "\n", $exemptionList );
		foreach ( $exemptionListLines as $line ) {
			// Only lines which begin with '*' are considered valid.
			if ( !str_starts_with( $line, '*' ) ) {
				continue;
			}

			$ips[] = trim( substr( $line, 1 ) );
		}
		return $ips;
	}

	/**
	 * Fetches an uncached list of IP addresses and/or ranges that are exempt from global autoblocks.
	 *
	 * @return array|false Array of IP addresses and/or ranges, or false on failure.
	 */
	private function fetchExemptIPAddresses() {
		// Attempt to get a URL for the central wiki, and if we can't get this URL then fallback to the exempt list on
		// the local wiki.
		$centralWikiUrl = $this->getForeignAPIQueryUrl();
		if ( !$centralWikiUrl ) {
			return $this->parseIPAddressList( $this->textFormatter->format(
				MessageValue::new( 'globalblocking-globalautoblock-exemptionlist' )
			) );
		}

		$req = $this->httpRequestFactory->create(
			$centralWikiUrl,
			[ 'userAgent' => $this->httpRequestFactory->getUserAgent() . ' GlobalAutoblockExemptionListProvider' ],
			__METHOD__
		);

		$status = $req->execute();

		[ $errorStatus, $warningStatus ] = $status->splitByErrorType();
		if ( !$warningStatus->isGood() ) {
			[ $errorText, $context ] = $this->statusFormatter->getPsr3MessageAndContext( $warningStatus );
			$this->logger->warning(
				$errorText,
				array_merge( $context, [ 'exception' => new RuntimeException() ] )
			);
		}

		if ( $errorStatus->isGood() ) {
			$json = $req->getContent();
			$decoded = FormatJson::decode( $json, true );

			if ( is_array( $decoded ) ) {
				$exemptListPage = $decoded['query']['pages'][0] ?? null;
				if ( $exemptListPage !== null ) {
					// Parse the content in the most recent revision. If there are no revisions, then the message
					// is empty which means that there are no IP addresses exempt from global autoblocks.
					$exemptListContent = $exemptListPage['revisions'][0]['slots']['main']['content'] ?? '';
					return $this->parseIPAddressList( $exemptListContent );
				}
			}
		}

		// If we could not fetch the exempt list from the central wiki, then return false so that a new attempt
		// to fetch the IPs is made the next time this method is called.
		[ $errorText, $context ] = $this->statusFormatter->getPsr3MessageAndContext( $errorStatus );
		$this->logger->error( $errorText, array_merge( [ 'exception' => new RuntimeException() ], $context ) );
		return false;
	}
}
