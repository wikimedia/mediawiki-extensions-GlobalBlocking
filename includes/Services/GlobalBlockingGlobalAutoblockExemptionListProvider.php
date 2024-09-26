<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use WANObjectCache;
use Wikimedia\IPUtils;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\Message\MessageValue;

/**
 * Provides access to the list of IP addresses which are exempt from global autoblocks.
 *
 * @since 1.43
 */
class GlobalBlockingGlobalAutoblockExemptionListProvider {
	private const CACHE_VERSION = 0;

	private ITextFormatter $textFormatter;
	private WANObjectCache $wanObjectCache;

	public function __construct(
		ITextFormatter $textFormatter,
		WANObjectCache $wanObjectCache
	) {
		$this->textFormatter = $textFormatter;
		$this->wanObjectCache = $wanObjectCache;
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
		return $this->wanObjectCache->getWithSetCallback(
			$this->getCacheKey(),
			ExpirationAwareness::TTL_HOUR,
			function () {
				return $this->fetchExemptIPAddresses();
			},
			[ 'version' => self::CACHE_VERSION, 'pcTTL' => ExpirationAwareness::TTL_PROC_SHORT ]
		);
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
			'GlobalAutoBlockExemptList'
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
	 * @return array
	 */
	private function fetchExemptIPAddresses(): array {
		return $this->parseIPAddressList( $this->textFormatter->format(
			MessageValue::new( 'globalblocking-globalautoblock-exemptionlist' )
		) );
	}
}
