<?php

namespace MediaWiki\Extension\GlobalBlocking\Hook;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "GlobalBlockingBlockedIpMsg" to register handlers implementing this interface.
 *
 * @ingroup Hooks
 * @deprecated Since 1.42 - Override the message key globalblocking-ipblocked on the wiki or using the
 *    MessageCacheFetchOverrides hook.
 */
interface GlobalBlockingBlockedIpMsgHook {

	/**
	 * Allow extensions to customise the message shown when a user is globally IP blocked.
	 *
	 * @param string &$errorMsg Translation key of the message shown to the user.
	 *
	 * @return bool|void True or no return value to continue or false to abort running remaining hook handlers.
	 * @deprecated Since 1.42 - Override the message key globalblocking-ipblocked on the wiki or using the
	 *    MessageCacheFetchOverrides hook.
	 */
	public function onGlobalBlockingBlockedIpMsg( string &$errorMsg );

}
