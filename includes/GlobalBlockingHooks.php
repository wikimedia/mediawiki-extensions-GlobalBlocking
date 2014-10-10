<?php

/**
 * MediaWiki hook handlers for the GlobalBlocking extension
 *
 * @license GNU GPL v2+
 */
class GlobalBlockingHooks {
	/**
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	static public function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$base = __DIR__ . '/..';
		switch ( $updater->getDB()->getType() ) {
			case 'sqlite':
			case 'mysql':
				$updater->addExtensionTable( 'globalblocks', "$base/globalblocking.sql" );
				$updater->addExtensionTable( 'global_block_whitelist', "$base/localdb_patches/setup-global_block_whitelist.sql" );
				break;

			default:
				// ERROR
				break;
		}
		return true;
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 * @param mixed $result
	 *
	 * @return bool
	 */
	static public function onGetUserPermissionsErrorsExpensive( Title &$title, User &$user, $action, &$result ) {
		global $wgApplyGlobalBlocks, $wgRequest;
		if ( $action == 'read' || !$wgApplyGlobalBlocks ) {
			return true;
		}
		if ( $user->isAllowed( 'ipblock-exempt' ) ||
			$user->isAllowed( 'globalblock-exempt' )
		) {
			// User is exempt from IP blocks.
			return true;
		}
		$ip = $wgRequest->getIP();
		$blockError = GlobalBlocking::getUserBlockErrors( $user, $ip );
		if ( !empty( $blockError ) ) {
			$result = array( $blockError );
			return false;
		}
		return true;
	}

	/**
	 * @param User $user
	 * @param string $ip
	 * @param bool $blocked
	 *
	 * @return bool
	 */
	static public function onUserIsBlockedGlobally( User &$user, $ip, &$blocked ) {
		$blockError = GlobalBlocking::getUserBlockErrors( $user, $ip );
		if ( $blockError ) {
			$blocked = true;
			return false;
		}
		return true;
	}

	/**
	 * @param $users
	 * @param $data
	 * @param $error
	 *
	 * @return bool
	 */
	static public function onSpecialPasswordResetOnSubmit( &$users, $data, &$error ) {
		global $wgUser, $wgRequest;

		if ( GlobalBlocking::getUserBlockErrors( $wgUser, $wgRequest->getIP() ) ) {
			$error = wfMessage( 'globalblocking-blocked-nopassreset' )->text();
			return false;
		}
		return true;
	}

	/**
	 * Creates a link to the global block log
	 * @param array $msg Message with a link to the global block log
	 * @param string $ip The IP address to be checked
	 *
	 * @return bool true
	 */
	static public function onOtherBlockLogLink( &$msg, $ip ) {
		// Fast return if it is a username. IP addresses can be blocked only.
		if ( !IP::isIPAddress( $ip ) ) {
			return true;
		}

		$block = GlobalBlocking::getGlobalBlockingBlock( $ip, true );
		if ( !$block ) {
			// Fast return if not globally blocked
			return true;
		}

		$msg[] = Html::rawElement(
			'span',
			array( 'class' => 'mw-globalblock-loglink plainlinks' ),
			wfMessage( 'globalblocking-loglink', $ip )->parse()
		);
		return true;
	}

	/**
	 * Show global block notice on Special:Contributions.
	 * @param int $userId
	 * @param User $user
	 * @param SpecialPage $sp
	 *
	 * @return bool
	 */
	static public function onSpecialContributionsBeforeMainOutput( $userId, User $user, SpecialPage $sp ) {
		if ( !$user->isAnon() ) {
			return true;
		}

		$rangeCondition = GlobalBlocking::getRangeCondition( $user->getName() );
		$out = $sp->getOutput();
		$pager = new GlobalBlockListPager( null, $rangeCondition );
		$pager->setLimit( 1 ); // show at most one entry
		$body = $pager->getBody();

		if ( $body != '' ) {
			$attribs = array( 'class' => 'mw-warning-with-logexcerpt' );
			$out->addHTML( Html::rawElement( 'div', $attribs,
				$sp->msg( 'globalblocking-contribs-notice', $user->getName() )->parse() .
				Html::rawElement( 'ul', array(), $body ) ) );
		}

		return true;
	}

	/**
	 * @param array $updateFields
	 *
	 * @return bool
	 */
	static public function onUserMergeAccountFields( array &$updateFields ) {
		$updateFields[] = array( 'global_block_whitelist', 'gbw_by', 'gbw_by_text' );

		return true;
	}
}
