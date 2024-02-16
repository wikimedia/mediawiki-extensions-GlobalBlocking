<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingBlockPurger;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
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
	'GlobalBlocking.GlobalBlockingBlockPurger' => static function (
		MediaWikiServices $services
	): GlobalBlockingBlockPurger {
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new GlobalBlockingBlockPurger(
			new ServiceOptions(
				GlobalBlockingBlockPurger::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$services->getDBLoadBalancerFactory(),
			$services->getReadOnlyMode()
		);
	},
	'GlobalBlocking.GlobalBlockLocalStatusLookup' => static function (
		MediaWikiServices $services
	): GlobalBlockLocalStatusLookup {
		return new GlobalBlockLocalStatusLookup( $services->getConnectionProvider() );
	},
];

// @codeCoverageIgnoreEnd
