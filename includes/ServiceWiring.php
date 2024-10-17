<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingBlockPurger;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalAutoblockExemptionListProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
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
		return new GlobalBlockLocalStatusLookup(
			$services->getConnectionProvider(),
			GlobalBlockingServices::wrap( $services )->getGlobalBlockingConnectionProvider(),
			$services->getCentralIdLookup(),
			$services->getAutoblockExemptionList()
		);
	},
	'GlobalBlocking.GlobalBlockLocalStatusManager' => static function (
		MediaWikiServices $services
	): GlobalBlockLocalStatusManager {
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new GlobalBlockLocalStatusManager(
			$globalBlockingServices->getGlobalBlockLocalStatusLookup(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingBlockPurger(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$services->getConnectionProvider()
		);
	},
	'GlobalBlocking.GlobalBlockLookup' => static function (
		MediaWikiServices $services
	): GlobalBlockLookup {
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new GlobalBlockLookup(
			new ServiceOptions(
				GlobalBlockLookup::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$services->getStatsdDataFactory(),
			$services->getCentralIdLookup(),
			$globalBlockingServices->getGlobalBlockLocalStatusLookup(),
			$services->getTempUserConfig(),
			$services->getUserFactory()
		);
	},
	'GlobalBlocking.GlobalBlockManager' => static function (
		MediaWikiServices $services
	): GlobalBlockManager {
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new GlobalBlockManager(
			new ServiceOptions(
				GlobalBlockManager::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$globalBlockingServices->getGlobalBlockingBlockPurger(),
			$globalBlockingServices->getGlobalBlockLookup(),
			$globalBlockingServices->getGlobalBlockingConnectionProvider(),
			$globalBlockingServices->getGlobalAutoblockExemptionListProvider(),
			$services->getCentralIdLookup(),
			$services->getUserFactory(),
			LoggerFactory::getInstance( 'GlobalBlocking' ),
			$services->getHookContainer()
		);
	},
	'GlobalBlocking.GlobalBlockingLinkBuilder' => static function (
		MediaWikiServices $services
	): GlobalBlockingLinkBuilder {
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new GlobalBlockingLinkBuilder(
			new ServiceOptions(
				GlobalBlockingLinkBuilder::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getLinkRenderer(),
			$globalBlockingServices->getGlobalBlockLookup()
		);
	},
	'GlobalBlocking.GlobalBlockingUserVisibilityLookup' => static function (
		MediaWikiServices $services
	): GlobalBlockingUserVisibilityLookup {
		return new GlobalBlockingUserVisibilityLookup( $services->getUserFactory() );
	},
	'GlobalBlocking.GlobalBlockingGlobalAutoblockExemptionListProvider' => static function (
		MediaWikiServices $services
	): GlobalBlockingGlobalAutoblockExemptionListProvider {
		return new GlobalBlockingGlobalAutoblockExemptionListProvider(
			new ServiceOptions(
				GlobalBlockingGlobalAutoblockExemptionListProvider::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getMessageFormatterFactory()->getTextFormatter( $services->getContentLanguage()->getCode() ),
			$services->getMainWANObjectCache(),
			$services->getHttpRequestFactory(),
			$services->getSiteLookup(),
			$services->getFormatterFactory()->getStatusFormatter( RequestContext::getMain() ),
			LoggerFactory::getInstance( 'GlobalBlocking' )
		);
	},
	'GlobalBlocking.GlobalBlockingGlobalBlockDetailsRenderer' => static function (
		MediaWikiServices $services
	): GlobalBlockingGlobalBlockDetailsRenderer {
		$globalBlockingServices = GlobalBlockingServices::wrap( $services );
		return new GlobalBlockingGlobalBlockDetailsRenderer(
			$services->getCentralIdLookup(),
			$services->getUserIdentityLookup(),
			$globalBlockingServices->getGlobalBlockingUserVisibilityLookup(),
			$globalBlockingServices->getGlobalBlockLocalStatusLookup(),
			$globalBlockingServices->getGlobalBlockingLinkBuilder()
		);
	}
];

// @codeCoverageIgnoreEnd
