<?php

namespace MediaWiki\Extension\GlobalBlocking\Hook;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "GlobalBlockingBlockedIpXffMsg" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface GlobalBlockingBlockedIpXffMsgHook {

	/**
	 * Allow extensions to customise the message shown when a user is globally IP rangeblocked, and the block is using
	 * the user's X-Forwarded-For header.
	 *
	 * @param string &$errorMsg Translation key of the message shown to the user.
	 * @return bool|void True or no return value to continue or false to abort running remaining hook handlers.
	 */
	public function onGlobalBlockingBlockedIpXffMsg( string &$errorMsg );

}
