<?php

namespace MediaWiki\Extension\GlobalBlocking\Hooks;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\HookContainer\HookContainer;

class HookRunner implements GlobalBlockingGetRetroactiveAutoblockIPsHook, GlobalBlockingGlobalBlockAuditHook {

	private HookContainer $container;

	public function __construct( HookContainer $container ) {
		$this->container = $container;
	}

	/** @inheritDoc */
	public function onGlobalBlockingGetRetroactiveAutoblockIPs( GlobalBlock $globalBlock, int $limit, array &$ips ) {
		$this->container->run(
			'GlobalBlockingGetRetroactiveAutoblockIPs',
			[ $globalBlock, $limit, &$ips ]
		);
	}

	/** @inheritDoc */
	public function onGlobalBlockingGlobalBlockAudit( GlobalBlock $globalBlock ) {
		$this->container->run(
			'GlobalBlockingGlobalBlockAudit',
			[ $globalBlock ]
		);
	}
}
