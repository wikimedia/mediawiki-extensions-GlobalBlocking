<?php

namespace MediaWiki\Extension\GlobalBlocking\Hook;

use MediaWiki\HookContainer\HookContainer;

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
