<?php

namespace MediaWiki\Extension\GlobalBlocking\Hook;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "GlobalBlockingBlockedIpXffMsg" to register handlers implementing this interface.
 *
 * @ingroup Hooks
 * @deprecated Since 1.42 - Override the message key globalblocking-ipblocked-xff on the wiki or using the
 *   MessageCacheFetchOverrides hook.
 */
interface GlobalBlockingBlockedIpXffMsgHook {

	/**
	 * Allow extensions to customise the message shown when a user is globally IP rangeblocked, and the block is using
	 * the user's X-Forwarded-For header.
	 *
	 * @param string &$errorMsg Translation key of the message shown to the user.
	 *
	 * @return bool|void True or no return value to continue or false to abort running remaining hook handlers.
	 * @deprecated Since 1.42 - Override the message key globalblocking-ipblocked-xff on the wiki or using the
	 *    MessageCacheFetchOverrides hook.
	 */
	public function onGlobalBlockingBlockedIpXffMsg( string &$errorMsg );

}
