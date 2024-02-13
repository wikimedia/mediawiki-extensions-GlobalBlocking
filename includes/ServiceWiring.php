<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockReasonFormatter;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

// PHPUnit does not understand coverage for this file.
// It is covered though, see GlobalBlockingServiceWiringTest.
// @codeCoverageIgnoreStart

return [
	'GlobalBlocking.GlobalBlockReasonFormatter' => static function (
		MediaWikiServices $services
	): GlobalBlockReasonFormatter {
		return new GlobalBlockReasonFormatter(
			new ServiceOptions(
				GlobalBlockReasonFormatter::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getMainWANObjectCache(),
			$services->getHttpRequestFactory(),
			LoggerFactory::getInstance( 'GlobalBlockReasonFormatter' )
		);
	},
	'GlobalBlocking.GlobalBlockingConnectionProvider' => static function (
		MediaWikiServices $services
	): GlobalBlockingConnectionProvider {
		return new GlobalBlockingConnectionProvider( $services->getConnectionProvider() );
	},
];

// @codeCoverageIgnoreEnd
