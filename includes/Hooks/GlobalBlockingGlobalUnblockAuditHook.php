<?php

namespace MediaWiki\Extension\GlobalBlocking\Hooks;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;

interface GlobalBlockingGlobalUnblockAuditHook {
	/**
	 * This hook is called after a global block is removed. Similar to
	 * {@link UnblockUserCompleteHook} but for GlobalBlocking global blocks. No return data is
	 * accepted; this hook is for auditing only.
	 *
	 * @since 1.46
	 * @param GlobalBlock $globalBlock The global block that was removed
	 * @return bool|void True or no return value to continue or false to abort
	 * @codeCoverageIgnore Cannot be annotated as covered.
	 */
	public function onGlobalBlockingGlobalUnblockAudit( GlobalBlock $globalBlock );
}
