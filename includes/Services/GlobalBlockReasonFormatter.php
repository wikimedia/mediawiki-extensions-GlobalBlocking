<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Json\FormatJson;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @author Taavi "Majavah" Väänänen <hi@taavi.wtf>
 */
class GlobalBlockReasonFormatter {
	private const CACHE_TTL = 3600;
	private const CACHE_VERSION = 0;

	/** @internal Only public for service wiring use. */
	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockRemoteReasonUrl',
	];

	/** @var ServiceOptions */
	private $options;

	/** @var WANObjectCache */
	private $wanObjectCache;

	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param ServiceOptions $options
	 * @param WANObjectCache $wanObjectCache
	 * @param HttpRequestFactory $httpRequestFactory
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		ServiceOptions $options,
		WANObjectCache $wanObjectCache,
		HttpRequestFactory $httpRequestFactory,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->wanObjectCache = $wanObjectCache;
		$this->httpRequestFactory = $httpRequestFactory;
		$this->logger = $logger;
	}

	/**
	 * @param string $wikitext
	 * @param string $langCode
	 * @return string
	 */
	public function format( string $wikitext, string $langCode ): string {
		$cacheKey = $this->wanObjectCache->makeGlobalKey(
			'GlobalBlocking',
			'BlockReason',
			$langCode,
			sha1( $this->options->get( 'GlobalBlockRemoteReasonUrl' ) ?? 'local' ),
			sha1( $wikitext )
		);

		// TODO: does this need poolcounter support?

		return $this->wanObjectCache->getWithSetCallback(
			$cacheKey,
			self::CACHE_TTL,
			function ( $oldValue, &$ttl, array &$setOpts ) use ( $wikitext, $langCode ) {
				return $this->expandRemoteTemplates( $wikitext, $langCode );
			},
			[ 'version' => self::CACHE_VERSION ]
		);
	}

	/**
	 * @param string $wikitext
	 * @param string $langCode
	 * @return string
	 */
	private function expandRemoteTemplates( string $wikitext, string $langCode ): string {
		$url = $this->options->get( 'GlobalBlockRemoteReasonUrl' );

		if ( !$url ) {
			// no remote url, fall back to local wikitext
			return $wikitext;
		}

		$url = wfAppendQuery(
			$url,
			[
				'action' => 'expandtemplates',
				'title' => 'Special:BlankPage/GlobalBlockReasonFormatter',
				'text' => $wikitext,
				'uselang' => $langCode,
				'prop' => 'wikitext',
				'formatversion' => 2,
				'format' => 'json',
			]
		);

		$req = $this->httpRequestFactory->create(
			$url,
			[
				'userAgent' => $this->httpRequestFactory->getUserAgent() . ' GlobalBlockReasonFormatter'
			],
			__METHOD__
		);

		$status = $req->execute();

		[ $errorStatus, $warningStatus ] = $status->splitByErrorType();
		if ( !$warningStatus->isGood() ) {
			$this->logger->warning(
				$warningStatus->getWikiText( false, false, 'en' ),
				[ 'exception' => new RuntimeException() ]
			);
		}

		if ( $errorStatus->isGood() ) {
			$json = $req->getContent();
			$decoded = FormatJson::decode( $json, true );

			if (
				is_array( $decoded )
				&& array_key_exists( 'expandtemplates', $decoded )
				&& array_key_exists( 'wikitext', $decoded['expandtemplates'] )
			) {
				return $decoded['expandtemplates']['wikitext'];
			}

			$this->logger->warning(
				'Got API response with unexpected formatting while parsing global block reason "{blockReason}"',
				[ 'response' => $decoded, 'blockReason' => $wikitext ]
			);
		} else {
			$this->logger->error(
				$warningStatus->getWikiText( false, false, 'en' ),
				[ 'exception' => new RuntimeException() ]
			);
		}

		// fallback to param
		return $wikitext;
	}
}
