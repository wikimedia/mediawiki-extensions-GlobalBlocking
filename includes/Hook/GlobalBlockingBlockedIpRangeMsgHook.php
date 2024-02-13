<?php

namespace MediaWiki\Extension\GlobalBlocking\Hook;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "GlobalBlockingBlockedIpRangeMsg" to register handlers implementing this interface.
 *
 * @ingroup Hooks
 * @deprecated Since 1.42 - Override the message key globalblocking-ipblocked-range on the wiki or using the
 *    MessageCacheFetchOverrides hook.
 */
interface GlobalBlockingBlockedIpRangeMsgHook {

	/**
	 * Allow extensions to customise the message shown when a user is globally IP rangeblocked.
	 *
	 * @param string &$errorMsg Translation key of the message shown to the user.
	 *
	 * @return bool|void True or no return value to continue or false to abort running remaining hook handlers.
	 * @deprecated Since 1.42 - Override the message key globalblocking-ipblocked-range on the wiki or using the
	 *    MessageCacheFetchOverrides hook.
	 */
	public function onGlobalBlockingBlockedIpRangeMsg( string &$errorMsg );

}
