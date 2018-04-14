<?php

/**
 * MediaWiki hook handlers for the GlobalBlocking extension
 *
 * @license GPL-2.0-or-later
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
	 * @param Title &$title
	 * @param User &$user
	 * @param string $action
	 * @param mixed &$result
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
	 * @param User &$user
	 * @param string $ip
	 * @param bool &$blocked
	 * @param Block|null &$block
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
	 * @param array &$users
	 * @param array $data
	 * @param string &$error
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
	 * @param array &$msg Message with a link to the global block log
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
		if ( !IP::isIPAddress( $name ) ) {
			return true;
		}

		// Obtain the first and the last IP of a range if IP is a range
		list( $startIP, $endIP ) = IP::parseRange( $name );
		substr( $startIP, 0, 4 );
		substr( $endIP, 0, 4 );
		$startname = IP::formatHex( $startIP );
		$endname = IP::formatHex( $endIP );

		// Get results from the first ip of a range
		$rangeConditionstart = GlobalBlocking::getRangeCondition( $startname );
		$pagerstart = new GlobalBlockListPager( $sp->getContext(), $rangeConditionstart );
		$pagerstart->setLimit( 1 ); // show at most one entry
		$bodystart = $pagerstart->getBody();

		// Get results from the last ip of a range
		$rangeConditionend = GlobalBlocking::getRangeCondition( $endname );
		$pagerend = new GlobalBlockListPager( $sp->getContext(), $rangeConditionend );
		$pagerend->setLimit( 1 ); // show at most one entry
		$bodyend = $pagerend->getBody();

		if ( $bodystart != '' && $bodyend != '' ) {
			$out = $sp->getOutput();
			$out->addHTML(
				Html::rawElement( 'div',
					[ 'class' => 'mw-warning-with-logexcerpt' ],
					$sp->msg( 'globalblocking-contribs-notice', $name )->parse() .
					Html::rawElement( 'ul', [], $bodystart )
				)
			);
		}

		return true;
	}

	/**
	 * @param array &$updateFields
	 *
	 * @return bool
	 */
	public static function onUserMergeAccountFields( array &$updateFields ) {
		$updateFields[] = [ 'global_block_whitelist', 'gbw_by', 'gbw_by_text' ];

		return true;
	}

	/**
	 * So users can just type in a username for target and it'll work
	 * @param array &$types
	 * @return bool
	 */
	public static function onGetLogTypesOnUser( array &$types ) {
		$types[] = 'gblblock';

		return true;
	}
}
