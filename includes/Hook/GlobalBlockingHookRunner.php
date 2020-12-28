<?php

namespace MediaWiki\Extension\GlobalBlocking\Hook;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;

/**
 * Hook runner for GlobalBlocking extension hooks.
 */
class GlobalBlockingHookRunner implements
	GlobalBlockingBlockedIpMsgHook,
	GlobalBlockingBlockedIpRangeMsgHook,
	GlobalBlockingBlockedIpXffMsgHook
{

	/** @var HookContainer */
	private $container;

	/**
	 * @param HookContainer $container
	 */
	public function __construct( HookContainer $container ) {
		$this->container = $container;
	}

	/**
	 * Convenience getter for static contexts
	 *
	 * See also core's Hooks::runner
	 *
	 * @return GlobalBlockingHookRunner
	 */
	public static function getRunner(): GlobalBlockingHookRunner {
		return new GlobalBlockingHookRunner(
			MediaWikiServices::getInstance()->getHookContainer()
		);
	}

	public function onGlobalBlockingBlockedIpMsg( string &$errorMsg ) {
		return $this->container->run(
			'GlobalBlockingBlockedIpMsg',
			[ &$errorMsg ]
		);
	}

	public function onGlobalBlockingBlockedIpRangeMsg( string &$errorMsg ) {
		return $this->container->run(
			'GlobalBlockingBlockedIpRangeMsg',
			[ &$errorMsg ]
		);
	}

	public function onGlobalBlockingBlockedIpXffMsg( string &$errorMsg ) {
		return $this->container->run(
			'GlobalBlockingBlockedIpXffMsg',
			[ &$errorMsg ]
		);
	}
}
