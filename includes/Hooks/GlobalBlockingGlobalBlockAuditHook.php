<?php

namespace MediaWiki\Extension\GlobalBlocking\Hooks;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;

interface GlobalBlockingGlobalBlockAuditHook {
	/**
	 * This hook is called after an IP address, IP range, or user is globally blocked. Similar to
	 * {@link BlockIpCompleteHook} but for GlobalBlocking global blocks. No return data is
	 * accepted; this hook is for auditing only.
	 *
	 * @since 1.44
	 * @param GlobalBlock $globalBlock The global block that was created or modified
	 * @return bool|void True or no return value to continue or false to abort
	 * @codeCoverageIgnore Cannot be annotated as covered.
	 */
	public function onGlobalBlockingGlobalBlockAudit( GlobalBlock $globalBlock );
}
