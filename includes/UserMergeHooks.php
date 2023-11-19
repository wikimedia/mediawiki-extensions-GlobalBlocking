<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Extension\UserMerge\Hooks\AccountFieldsHook;

/**
 * MediaWiki hook handlers for the GlobalBlocking extension
 * All hooks from the UserMerge extension which is optional to use with this extension.
 *
 * @license GPL-2.0-or-later
 */
class UserMergeHooks implements
	AccountFieldsHook
{
	/**
	 * @param array &$updateFields
	 */
	public function onUserMergeAccountFields( array &$updateFields ): void {
		$updateFields[] = [ 'global_block_whitelist', 'gbw_by', 'gbw_by_text' ];
	}
}
