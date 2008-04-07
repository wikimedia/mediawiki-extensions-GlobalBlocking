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
$wgExtensionFunctions[] = 'wfGlobalBlocking';
$wgExtensionCredits['other'][] = array(
	'name' => 'GlobalBlocking',
	'author' => 'Andrew Garrett',
	'description' => 'Allows IP addresses to be blocked across multiple wikis',
	'descriptionmsg' => 'globalblocking_description',
	'url' => 'http://www.mediawiki.org/wiki/Extension:GlobalBlocking',
);

$wgExtensionMessagesFiles['GlobalBlocking'] =  "$dir/GlobalBlocking.i18n.php";
$wgHooks['getUserPermissionsErrorsExpensive'][] = 'gbGetUserPermissionsErrors';

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

function wfGlobalBlocking() {
	wfLoadExtensionMessages( 'GlobalBlocking' );
}

function gbGetUserPermissionsErrors( &$title, &$user, &$action, &$result ) {
	global $wgUser;
	$dbr = gbGetGlobalBlockingSlave();
	$ip = wfGetIp();

	$conds = array( 'gb_address' => $ip, 'gb_timestamp<'.$dbr->timestamp(wfTimestampNow()) );
	
	if (!$wgUser->isAnon())
		$conds['gb_anon_only'] = 0;
		
	// Get the blocks
	$res = $dbr->select( 'globalblocks', '*', $conds );
	
	if ($dbr->numRows( $res )) {
		if (!is_array($result)) {
			$result = $result ? array($result) : array();
		}
		$block = $dbr->fetchObject( $res );
		
		$expiry = Block::decodeExpiry( $block->gb_expiry );
		if ($expiry == 'infinity') {
			$expiry = wfMsg( 'infiniteblock' );
		} else {
			global $wgLang;
			$expiry = $wgLang->timeanddate( wfTimestamp( TS_MW, $expiry ), true );
		}
		
		$result[] = array('globalblocking-blocked', $block->gb_by, $block->gb_by_wiki, $block->gb_reason, $expiry);
		return false;
	}

	return true;
}

function gbGetGlobalBlockingMaster() {
	global $wgGlobalBlockingDatabase;
	return wfGetDB( DB_MASTER, 'globalblocking', $wgGlobalBlockingDatabase );
}

function gbGetGlobalBlockingSlave() {
	global $wgGlobalBlockingDatabase;
	return wfGetDB( DB_SLAVE, 'globalblocking', $wgGlobalBlockingDatabase );
}

function gbBuildForm( $fields, $submitLabel ) {
	$form = '';
	$form .= "<table><tbody>";
	
	foreach( $fields as $labelmsg => $input ) {
		$id = "mw-gb-$labelmsg";
		$form .= Xml::openElement( 'tr', array( 'class' => $id ) );
		
		$form .= Xml::element( 'td', array(), wfMsg( $labelmsg ) );
		
		$form .= Xml::openElement( 'td' ) . $input . Xml::closeElement( 'td' );
		
		$form .= Xml::closeElement( 'tr' );
	}
	
	$form .= "</tbody></table>";
	
	$form .= wfSubmitButton( wfMsg($submitLabel) );
	
	return $form;
}

function gbGetGlobalBlockId( $ip ) {
	$dbr = gbGetGlobalBlockingSlave();
	
	$res = $dbr->select( 'globalblocks', 'gb_id', array( 'gb_address' => $ip ) );
	
	if ($dbr->numRows($res) == 0) {
		return 0;
	}
	
	$row = $dbr->fetchObject( $res );
	
	return $row->gb_id;
}