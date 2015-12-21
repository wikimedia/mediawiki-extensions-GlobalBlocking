<?php

/**
 * Static utility class of the GlobalBlocking extension.
 *
 * @license GNU GPL v2+
 */
class GlobalBlocking {

	/**
	 * @param $user User
	 * @param $ip string
	 * @return array: empty or a message key with parameters
	 * @throws MWException
	 */
	static function getUserBlockErrors( $user, $ip ) {
		global $wgLang, $wgRequest, $wgGlobalBlockingBlockXFF;
		static $result = null;

		// Instance cache
		if ( !is_null( $result ) ) {
			return $result;
		}

		if ( $user->isAllowed( 'ipblock-exempt' ) || $user->isAllowed( 'globalblock-exempt' ) ) {
			// User is exempt from IP blocks.
			return $result = array();
		}

		$block = self::getGlobalBlockingBlock( $ip, $user->isAnon() );
		if ( $block ) {
			// Check for local whitelisting
			if ( GlobalBlocking::getWhitelistInfo( $block->gb_id ) ) {
				// Block has been whitelisted.
				return $result = array();
			}

			$blockTimestamp = $wgLang->timeanddate( wfTimestamp( TS_MW, $block->gb_timestamp ), true );
			$blockExpiry = $wgLang->formatExpiry( $block->gb_expiry );
			$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
			$blockingUser = self::maybeLinkUserpage( $block->gb_by_wiki, $block->gb_by );
			if ( IP::isValid( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked';
				$hookName = 'GlobalBlockingBlockedIpMsg';
				$apiErrorInfo = 'You have been globally blocked from editing';
			} elseif ( IP::isValidBlock( $block->gb_address ) ) {
				$errorMsg = 'globalblocking-ipblocked-range';
				$hookName = 'GlobalBlockingBlockedIpRangeMsg';
				$apiErrorInfo = 'Your IP is in a range that has been globally blocked from editing';
			} else {
				throw new MWException( "This should not happen. IP globally blocked is not valid and is not a valid range?" );
			}

			// Allow site customization of blocked message.
			Hooks::run( $hookName, array( &$errorMsg ) );
			ApiBase::$messageMap[$errorMsg] = array(
				'code' => $errorMsg,
				'info' => $apiErrorInfo,
			);
			return $result = array( $errorMsg, $blockingUser, $display_wiki, $block->gb_reason,
				$blockTimestamp, $blockExpiry, $ip, $block->gb_address );
		}

		if ( $wgGlobalBlockingBlockXFF ) {
			$xffIps = $wgRequest->getHeader( 'X-Forwarded-For' );
			if ( $xffIps ) {
				$xffIps = array_map( 'trim', explode( ',', $xffIps ) );
				$blocks = self::checkIpsForBlock( $xffIps, $user->isAnon() );
				if ( count( $blocks ) > 0 ) {
					list ( $blockIP, $block ) = self::getAppliedBlock( $xffIps, $blocks );
					$blockTimestamp = $wgLang->timeanddate(
						wfTimestamp( TS_MW, $block->gb_timestamp ),
						true
					);
					$blockExpiry = $wgLang->formatExpiry( $block->gb_expiry );
					$display_wiki = WikiMap::getWikiName( $block->gb_by_wiki );
					$blockingUser = self::maybeLinkUserpage( $block->gb_by_wiki, $block->gb_by );
					// Allow site customization of blocked message.
					$blockedIpXffMsg = 'globalblocking-ipblocked-xff';
					Hooks::run( 'GlobalBlockingBlockedIpXffMsg', array( &$blockedIpXffMsg ) );
					ApiBase::$messageMap[$blockedIpXffMsg] = array(
						'code' => $blockedIpXffMsg,
						'info' => 'One or more proxy servers used by your request has been globally blocked',
					);
					return $result = array(
						$blockedIpXffMsg,
						$blockingUser,
						$display_wiki,
						$block->gb_reason,
						$blockTimestamp,
						$blockExpiry,
						$blockIP
					);
				}
			}
		}

		return $result = array();
	}

	/**
	 * Get a block
	 * @param string $ip The IP address to be checked
	 * @param boolean $anon Get anon blocks only
	 * @return object The block
	 */
	static function getGlobalBlockingBlock( $ip, $anon ) {
		$dbr = GlobalBlocking::getGlobalBlockingDatabase( DB_SLAVE );

		$conds = self::getRangeCondition( $ip );

		if ( !$anon ) {
			$conds['gb_anon_only'] = 0;
		}

		// Get the block
		$block = $dbr->selectRow( 'globalblocks', '*', $conds, __METHOD__ );
		return $block;
	}

	/**
	 * Get a database range condition for an IP address
	 * @param string $ip The IP address
	 * @return array a SQL condition
	 */
	static function getRangeCondition( $ip ) {
		$dbr = GlobalBlocking::getGlobalBlockingDatabase( DB_SLAVE );

		$hex_ip = IP::toHex( $ip );
		$ip_pattern = substr( $hex_ip, 0, 4 ) . '%'; // Don't bother checking blocks out of this /16.

		$cond = array(
			'gb_range_end>=' . $dbr->addQuotes( $hex_ip ), // This block in the given range.
			'gb_range_start<=' . $dbr->addQuotes( $hex_ip ),
			'gb_range_start like ' . $dbr->addQuotes( $ip_pattern ),
			'gb_expiry>' . $dbr->addQuotes( $dbr->timestamp( wfTimestampNow() ) )
		);

		return $cond;
	}

	/**
	 * Check an array of IPs for a block on any
	 * @param array $ips The Array of IP addresses to be checked
	 * @param boolean $anon Get anon blocks only
	 * @return array of applicable blocks
	 */
	static function checkIpsForBlock( $ips, $anon ) {
		$dbr = GlobalBlocking::getGlobalBlockingDatabase( DB_SLAVE );
		$conds = array();
		foreach ( $ips as $ip ) {
			if ( IP::isValid( $ip ) ) {
				$conds[] = $dbr->makeList( self::getRangeCondition( $ip ), LIST_AND );
			}
		}

		if ( !$conds ) {
			// No valid IPs provided so don't even make the query. Bug 59705
			return array();
		}
		$conds = array( $dbr->makeList( $conds, LIST_OR ) );

		if ( !$anon ) {
			$conds['gb_anon_only'] = 0;
		}

		$blocks = array();
		$results = $dbr->select( 'globalblocks', '*', $conds, __METHOD__ );
		if ( !$results ) {
			return array();
		}

		foreach ( $results as $block ) {
			if ( !GlobalBlocking::getWhitelistInfo( $block->gb_id ) ) {
				$blocks[] = $block;
			}
		}

		return $blocks;
	}

	/**
	 * From a list of XFF ips, and list of blocks that apply, choose the block that will
	 * be shown to the end user. Using the first block in the array for now.
	 *
	 * @param array $ips The Array of IP addresses to be checked
	 * @param array $blocks The Array of blocks (db rows)
	 * @return array|null ($ip, $block) the chosen ip and block
	 */
	private static function getAppliedBlock( $ips, $blocks ) {
		$block = array_shift( $blocks );
		foreach ( $ips as $ip ) {
			$ipHex = IP::toHex( $ip );
			if ( $block->gb_range_start <= $ipHex && $block->gb_range_end >= $ipHex ) {
				return array( $ip, $block );
			}
		}

		return null;
	}

	/**
	 * @param int $dbtype either DB_SLAVE or DB_MASTER
	 * @return DatabaseBase
	 */
	static function getGlobalBlockingDatabase( $dbtype ) {
		global $wgGlobalBlockingDatabase;
		return wfGetDB( $dbtype, 'globalblocking', $wgGlobalBlockingDatabase );
	}

	/**
	 * @param string $ip
	 * @param int $dbtype either DB_SLAVE or DB_MASTER
	 * @return int
	 */
	static function getGlobalBlockId( $ip, $dbtype = DB_SLAVE ) {
		$db = self::getGlobalBlockingDatabase( $dbtype );

		if ( !( $row = $db->selectRow( 'globalblocks', 'gb_id', array( 'gb_address' => $ip ), __METHOD__ ) ) ) {
			return 0;
		}

		return $row->gb_id;
	}

	static function purgeExpired() {
		// This is expensive. It involves opening a connection to a new master,
		// and doing a write query. We should only do it when a connection to the master
		// is already open (currently, when a global block is made).
		$dbw = GlobalBlocking::getGlobalBlockingDatabase( DB_MASTER );

		// Stand-alone transaction.
		$dbw->begin();
		$dbw->delete( 'globalblocks', array( 'gb_expiry<' . $dbw->addQuotes( $dbw->timestamp() ) ), __METHOD__ );
		$dbw->commit();

		// Purge the global_block_whitelist table.
		// We can't be perfect about this without an expensive check on the master
		// for every single global block. However, we can be clever about it and store
		// the expiry of global blocks in the global_block_whitelist table.
		// That way, most blocks will fall out of the table naturally when they expire.
		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin();
		$dbw->delete( 'global_block_whitelist', array( 'gbw_expiry<' . $dbw->addQuotes( $dbw->timestamp() ) ), __METHOD__ );
		$dbw->commit();
	}

	/**
	 * @param $id null|int
	 * @param $address null|string
	 * @return array|bool
	 * @throws Exception
	 */
	static function getWhitelistInfo( $id = null, $address = null ) {
		if ( $id != null ) {
			$conds = array( 'gbw_id' => $id );
		} elseif ( $address != null ) {
			$conds = array( 'gbw_address' => $address );
		} else {
			//WTF?
			throw new Exception( "Neither Block IP nor Block ID given for retrieving whitelist status" );
		}

		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow( 'global_block_whitelist', array( 'gbw_by', 'gbw_reason' ), $conds, __METHOD__ );

		if ( $row == false ) {
			// Not whitelisted.
			return false;
		} else {
			// Block has been whitelisted
			return array( 'user' => $row->gbw_by, 'reason' => $row->gbw_reason );
		}
	}

	/**
	 * @param $block_ip string
	 * @return array|bool
	 */
	static function getWhitelistInfoByIP( $block_ip ) {
		return self::getWhitelistInfo( null, $block_ip );
	}

	/**
	 * @param $wiki_id
	 * @param $user string
	 * @return string
	 */
	static function maybeLinkUserpage( $wiki_id, $user ) {
		if ( class_exists( 'WikiMap' ) ) {
			$wiki = WikiMap::getWiki( $wiki_id );

			if ( $wiki ) {
				return "[" . $wiki->getFullUrl( "User:$user" ) . " $user]";
			}
		}
		return $user;
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string|bool $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return array
	 */
	static function insertBlock( $address, $reason, $expiry, $blocker, $options = array() ) {
		$errors = array();

		## Purge expired blocks.
		GlobalBlocking::purgeExpired();

		## Validate input
		$ip = IP::sanitizeIP( $address );

		$anonOnly = in_array( 'anon-only', $options );
		$modify = in_array( 'modify', $options );

		if ( !IP::isIPAddress( $ip ) ) {
			// Invalid IP address.
			$errors[] = array( 'globalblocking-block-ipinvalid', $ip );
		}

		if ( false === $expiry ) {
			$errors[] = array( 'globalblocking-block-expiryinvalid' );
		}

		// Check for too-big ranges.
		list( $range_start, $range_end ) = IP::parseRange( $ip );

		if ( substr( $range_start, 0, 4 ) != substr( $range_end, 0, 4 ) ) {
			// Range crosses a /16 boundary.
			$errors[] = array( 'globalblocking-block-bigrange', $ip );
		}

		// Normalise the range
		if ( $range_start != $range_end ) {
			$ip = IP::sanitizeRange( $ip );
		}

		// Check for an existing block in the master database
		$existingBlock = GlobalBlocking::getGlobalBlockId( $ip, DB_MASTER );
		if ( !$modify && $existingBlock ) {
			$errors[] = array( 'globalblocking-block-alreadyblocked', $ip );
		}

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		// We're a-ok.
		$dbw = GlobalBlocking::getGlobalBlockingDatabase( DB_MASTER );

		// Delete the old block, if applicable

		if ( $modify ) {
			$dbw->delete( 'globalblocks', array( 'gb_id' => $existingBlock ), __METHOD__ );
		}

		$row = array();
		$row['gb_address'] = $ip;
		$row['gb_by'] = $blocker->getName();
		$row['gb_by_wiki'] = wfWikiId();
		$row['gb_reason'] = $reason;
		$row['gb_timestamp'] = $dbw->timestamp( wfTimestampNow() );
		$row['gb_anon_only'] = $anonOnly;
		$row['gb_expiry'] = $dbw->encodeExpiry( $expiry, $dbw );
		list( $row['gb_range_start'], $row['gb_range_end'] ) = array( $range_start, $range_end );

		$dbw->insert( 'globalblocks', $row, __METHOD__, array( 'IGNORE' ) );
		if ( !$dbw->affectedRows() ) {
			// Race condition, the IP is already blocked (bug 67815)
			return array( array( 'globalblocking-block-alreadyblocked', $ip ) );
		}

		return array();
	}

	/**
	 * @param string $address
	 * @param string $reason
	 * @param string $expiry
	 * @param User $blocker
	 * @param array $options
	 * @return array
	 */
	static function block( $address, $reason, $expiry, $blocker, $options = array() ) {
		global $wgContLang;

		$expiry = SpecialBlock::parseExpiryInput( $expiry );
		$errors = self::insertBlock( $address, $reason, $expiry, $blocker, $options );

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		$anonOnly = in_array( 'anon-only', $options );
		$modify = in_array( 'modify', $options );

		// Log it.
		$logAction = $modify ? 'modify' : 'gblock2';
		$flags = array();

		if ( $anonOnly ) {
			$flags[] = wfMessage( 'globalblocking-list-anononly' )->inContentLanguage()->text();
		}

		if ( $expiry != 'infinity' ) {
			$displayExpiry = $wgContLang->timeanddate( $expiry );
			$flags[] = wfMessage( 'globalblocking-logentry-expiry', $displayExpiry )
				->inContentLanguage()->text();
		} else {
			$flags[] = wfMessage( 'globalblocking-logentry-noexpiry' )->inContentLanguage()->text();
		}

		$info = implode( ', ', $flags );

		$page = new LogPage( 'gblblock' );
		$page->addEntry( $logAction,
			Title::makeTitleSafe( NS_USER, $address ),
			$reason,
			array( $info, $address )
		);

		return array();
	}

	/**
	 * Build links to other global blocking special pages, shown in the subtitle
	 * @param SpecialPage $sp SpecialPage instance for context
	 * @return string links to special pages
	 */
	static function buildSubtitleLinks( SpecialPage $sp ) {
		// Add a few useful links
		$links = array();
		$pagetype = $sp->getName();

		// Don't show a link to a special page on the special page itself.
		// Show the links only if the user has sufficient rights
		if ( $pagetype != 'GlobalBlockList' ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockList' );
			$links[] = Linker::linkKnown( $title, $sp->msg( 'globalblocklist' )->escaped() );
		}
		$canBlock = $sp->getUser()->isAllowed( 'globalblock' );
		if ( $pagetype != 'GlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlock' );
			$links[] = Linker::linkKnown( $title, $sp->msg( 'globalblocking-goto-block' )->escaped() );
		}
		if ( $pagetype != 'RemoveGlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'RemoveGlobalBlock' );
			$links[] = Linker::linkKnown( $title, $sp->msg( 'globalblocking-goto-unblock' )->escaped() );
		}
		if ( $pagetype != 'GlobalBlockStatus' && $sp->getUser()->isAllowed( 'globalblock-whitelist' ) ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockStatus' );
			$links[] = Linker::linkKnown( $title, $sp->msg( 'globalblocking-goto-status' )->escaped() );
		}
		if ( $pagetype == 'GlobalBlock' && $sp->getUser()->isAllowed( 'editinterface' ) ) {
			$title = Title::makeTitle( NS_MEDIAWIKI, 'Globalblocking-block-reason-dropdown' );
			$links[] = Linker::linkKnown(
				$title,
				$sp->msg( 'globalblocking-block-edit-dropdown' )->escaped(),
				array(),
				array( 'action' => 'edit' )
			);
		}
		$linkItems = count( $links ) ? $sp->msg( 'parentheses', $sp->getLanguage()->pipeList( $links ) )->text() : '';
		return $linkItems;
	}
}
