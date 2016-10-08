<?php

/**
 * MediaWiki hook handlers for the GlobalBlocking extension
 *
 * @license GNU GPL v2+
 */
class GlobalBlockingHooks {
	/**
	 * Extension registration callback
	 */
	public static function onRegistration() {
		global $wgWikimediaJenkinsCI, $wgGlobalBlockingDatabase, $wgDBname;

		// Override $wgGlobalBlockingDatabase for Wikimedia Jenkins.
		if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI ) {
			$wgGlobalBlockingDatabase = $wgDBname;
		}
	}

	/**
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$base = __DIR__ . '/..';
		switch ( $updater->getDB()->getType() ) {
			case 'sqlite':
			case 'mysql':
				$updater->addExtensionTable(
					'globalblocks',
					"$base/globalblocking.sql"
				);
				$updater->addExtensionTable(
					'global_block_whitelist',
					"$base/localdb_patches/setup-global_block_whitelist.sql"
				);
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
	public static function onGetUserPermissionsErrorsExpensive(
		Title &$title, User &$user, $action, &$result
	) {
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
			$result = [ $blockError ];
			return false;
		}
		return true;
	}

	/**
	 * @param User $user
	 * @param string $ip
	 * @param bool $blocked
	 * @param Block|null $block
	 *
	 * @return bool
	 */
	public static function onUserIsBlockedGlobally( User &$user, $ip, &$blocked, &$block ) {
		$block = GlobalBlocking::getUserBlock( $user, $ip );
		if ( $block !== null ) {
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
	public static function onSpecialPasswordResetOnSubmit( &$users, $data, &$error ) {
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
	public static function onOtherBlockLogLink( &$msg, $ip ) {
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
			[ 'class' => 'mw-globalblock-loglink plainlinks' ],
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
	public static function onSpecialContributionsBeforeMainOutput(
		$userId, User $user, SpecialPage $sp
	) {
		$name = $user->getName();
		if ( !IP::isValid( $name ) ) {
			return true;
		}

		$rangeCondition = GlobalBlocking::getRangeCondition( $name );
		$pager = new GlobalBlockListPager( $sp->getContext(), $rangeCondition );
		$pager->setLimit( 1 ); // show at most one entry
		$body = $pager->getBody();

		if ( $body != '' ) {
			$out = $sp->getOutput();
			$out->addHTML(
				Html::rawElement( 'div',
					[ 'class' => 'mw-warning-with-logexcerpt' ],
					$sp->msg( 'globalblocking-contribs-notice', $name )->parse() .
					Html::rawElement( 'ul', [], $body )
				)
			);
		}

		return true;
	}

	/**
	 * @param array $updateFields
	 *
	 * @return bool
	 */
	public static function onUserMergeAccountFields( array &$updateFields ) {
		$updateFields[] = [ 'global_block_whitelist', 'gbw_by', 'gbw_by_text' ];

		return true;
	}
}
