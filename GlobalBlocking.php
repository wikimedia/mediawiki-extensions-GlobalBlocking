<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

/**#@+
 * Provides a way to block an IP Address over multiple wikis sharing a database.
 * Requires
 * @addtogroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:GlobalBlocking Documentation
 *
 *
 * @author Andrew Garrett <andrew@epstone.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
$dir = dirname(__FILE__);
$wgExtensionCredits['other'][] = array(
	'name'           => 'GlobalBlocking',
	'author'         => 'Andrew Garrett',
	'version'        => preg_replace('/^.* (\d\d\d\d-\d\d-\d\d) .*$/', '\1', '$LastChangedDate$'), #just the date of the last change
	'description'    => 'Allows IP addresses to be blocked across multiple wikis',
	'descriptionmsg' => 'globalblocking-desc',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:GlobalBlocking',
);

$wgExtensionMessagesFiles['GlobalBlocking'] =  "$dir/GlobalBlocking.i18n.php";
$wgHooks['getUserPermissionsErrorsExpensive'][] = 'GlobalBlocking::getUserPermissionsErrors';

$wgAutoloadClasses['SpecialGlobalBlock'] = "$dir/SpecialGlobalBlock.php";
$wgSpecialPages['GlobalBlock'] = 'SpecialGlobalBlock';
$wgAutoloadClasses['SpecialGlobalBlockList'] = "$dir/SpecialGlobalBlockList.php";
$wgSpecialPages['GlobalBlockList'] = 'SpecialGlobalBlockList';

## Add global block log
$wgLogTypes[] = 'gblblock';
$wgLogNames['gblblock'] = 'globalblocking-logpage';
$wgLogHeaders['gblblock'] = 'globalblocking-logpagetext';
$wgLogActions['gblblock/gblock'] = 'globalblocking-block-logentry';
$wgLogActions['gblblock/gunblock'] = 'globalblocking-unblock-logentry';

## Permissions
$wgGroupPermissions['steward']['globalblock'] = true;
$wgGroupPermissions['steward']['globalunblock'] = true;

## CONFIGURATION
/**
 * Database name you keep global blocking data in.
 *
 * If this is not on the primary database connection, don't forget
 * to also set up $wgDBservers to have an entry with a groupLoads
 * setting for the 'GlobalBlocking' group.
 */
$wgGlobalBlockingDatabase = 'globalblocking';

/**
 * Whether to respect global blocks on this wiki. This is used so that
 * global blocks can be set one one wiki, but not actually applied there
 * (i.e. so people can contest them on that wiki.
 */
$wgApplyGlobalBlocks = true;

class GlobalBlocking {
	static function getUserPermissionsErrors( &$title, &$user, &$action, &$result ) {
		global $wgApplyGlobalBlocks;
		if ($action == 'read' || !$wgApplyGlobalBlocks) {
			return true;
		}
		
		global $wgUser;
		$dbr = GlobalBlocking::getGlobalBlockingSlave();
		$ip = wfGetIp();
	
		$conds = array( 'gb_address' => $ip, 'gb_timestamp<'.$dbr->addQuotes($dbr->timestamp(wfTimestampNow())) );
	
		if (!$wgUser->isAnon())
			$conds['gb_anon_only'] = 0;
	
		// Get the block
		if ($block = $dbr->selectRow( 'globalblocks', '*', $conds, __METHOD__ )) {

			$expiry = Block::decodeExpiry( $block->gb_expiry );
			if ($expiry == 'infinity') {
				$expiry = wfMsg( 'infiniteblock' );
			} else {
				global $wgLang;
				$expiry = $wgLang->timeanddate( wfTimestamp( TS_MW, $expiry ), true );
			}
	
			wfLoadExtensionMessages( 'GlobalBlocking' );
			
			$result[] = array('globalblocking-blocked', $block->gb_by, $block->gb_by_wiki, $block->gb_reason, $expiry);
			return false;
		}
	
		return true;
	}
	
	static function getGlobalBlockingMaster() {
		global $wgGlobalBlockingDatabase;
		return wfGetDB( DB_MASTER, 'globalblocking', $wgGlobalBlockingDatabase );
	}
	
	static function getGlobalBlockingSlave() {
		global $wgGlobalBlockingDatabase;
		return wfGetDB( DB_SLAVE, 'globalblocking', $wgGlobalBlockingDatabase );
	}
	
	static function buildForm( $fields, $submitLabel ) {
		return wfBuildForm( $fields, $submitLabel );
	}
	
	static function getGlobalBlockId( $ip ) {
		$dbr = GlobalBlocking::getGlobalBlockingSlave();
	
		if (!($row = $dbr->selectRow( 'globalblocks', 'gb_id', array( 'gb_address' => $ip ), __METHOD__ )))
			return 0;
	
		return $row->gb_id;
	}
	
	static function purgeExpired() {
		// This is expensive. It involves opening a connection to a new master,
		// and doing a write query. We should only do it when a connection to the master
		// is already open (currently, when a global block is made).
		$dbw = GlobalBlocking::getGlobalBlockingMaster();
		
		// Stand-alone transaction.
		$dbw->begin();
		$dbw->delete( 'globalblocks', array('gb_expiry<'.$dbw->addQuotes($dbw->timestamp())), __METHOD__ );
		$dbw->commit();
	}
}