<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\GlobalBlocking\Hooks;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;

interface GlobalBlockingGlobalBlockLocalStatusChangeAuditHook {
	/**
	 * This hook is called after a global block is locally disabled or locally re-enabled on a wiki.
	 * No return data is accepted; this hook is for auditing only.
	 *
	 * @since 1.47
	 * @param GlobalBlock $globalBlock The global block that had its local status changed
	 * @param bool $isLocallyDisabled True if the block is now locally disabled, false if it was re-enabled
	 * @param string|false $wikiId The wiki where the local status was changed, or false for the local wiki
	 * @codeCoverageIgnore Cannot be annotated as covered.
	 */
	public function onGlobalBlockingGlobalBlockLocalStatusChangeAudit(
		GlobalBlock $globalBlock, bool $isLocallyDisabled, string|false $wikiId
	): void;
}
