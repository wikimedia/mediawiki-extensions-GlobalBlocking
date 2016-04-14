<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

/**#@+
 * Provides a way to block an IP Address over multiple wikis sharing a database.
 * Requires
 *
 * @file
 * @ingroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:GlobalBlocking Documentation
 *
 *
 * @author Andrew Garrett <andrew@epstone.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'GlobalBlocking',
	'author'         => 'Andrew Garrett',
	'descriptionmsg' => 'globalblocking-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:GlobalBlocking',
	'license-name'   => 'GPL-2.0+',
);

$wgMessagesDirs['GlobalBlocking'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['GlobalBlockingAlias'] = __DIR__ . '/GlobalBlocking.alias.php';

$wgHooks['getUserPermissionsErrorsExpensive'][] = 'GlobalBlockingHooks::onGetUserPermissionsErrorsExpensive';
$wgHooks['UserIsBlockedGlobally'][] = 'GlobalBlockingHooks::onUserIsBlockedGlobally';
$wgHooks['SpecialPasswordResetOnSubmit'][] = 'GlobalBlockingHooks::onSpecialPasswordResetOnSubmit';
$wgHooks['OtherBlockLogLink'][] = 'GlobalBlockingHooks::onOtherBlockLogLink';
$wgHooks['SpecialContributionsBeforeMainOutput'][] = 'GlobalBlockingHooks::onSpecialContributionsBeforeMainOutput';
$wgHooks['UserMergeAccountFields'][] = 'GlobalBlockingHooks::onUserMergeAccountFields';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'GlobalBlockingHooks::onLoadExtensionSchemaUpdates';

$wgSpecialPages['GlobalBlock'] = 'SpecialGlobalBlock';
$wgSpecialPages['GlobalBlockList'] = 'SpecialGlobalBlockList';
$wgSpecialPages['GlobalBlockStatus'] = 'SpecialGlobalBlockStatus';
$wgSpecialPages['RemoveGlobalBlock'] = 'SpecialRemoveGlobalBlock';

$wgAPIModules['globalblock'] = 'ApiGlobalBlock';
$wgAPIListModules['globalblocks'] = 'ApiQueryGlobalBlocks';

$wgAutoloadClasses['SpecialGlobalBlock'] = __DIR__ . '/includes/specials/SpecialGlobalBlock.php';
$wgAutoloadClasses['SpecialGlobalBlockList'] = __DIR__ . '/includes/specials/SpecialGlobalBlockList.php';
$wgAutoloadClasses['GlobalBlockListPager'] = __DIR__ . '/includes/specials/SpecialGlobalBlockList.php';
$wgAutoloadClasses['SpecialGlobalBlockStatus'] = __DIR__ . '/includes/specials/SpecialGlobalBlockStatus.php';
$wgAutoloadClasses['SpecialRemoveGlobalBlock'] = __DIR__ . '/includes/specials/SpecialRemoveGlobalBlock.php';
$wgAutoloadClasses['ApiQueryGlobalBlocks'] = __DIR__ . '/includes/api/ApiQueryGlobalBlocks.php';
$wgAutoloadClasses['ApiGlobalBlock'] = __DIR__ . '/includes/api/ApiGlobalBlock.php';
$wgAutoloadClasses['GlobalBlocking'] = __DIR__ . '/includes/GlobalBlocking.class.php';
$wgAutoloadClasses['GlobalBlockingHooks'] = __DIR__ . '/includes/GlobalBlockingHooks.php';

## Add global block log
$wgLogTypes[] = 'gblblock';
$wgLogNames['gblblock'] = 'globalblocking-logpage';
$wgLogHeaders['gblblock'] = 'globalblocking-logpagetext';
$wgLogActions['gblblock/gblock'] = 'globalblocking-block-logentry';
$wgLogActions['gblblock/gblock2'] = 'globalblocking-block2-logentry';
$wgLogActions['gblblock/gunblock'] = 'globalblocking-unblock-logentry';
$wgLogActions['gblblock/whitelist'] = 'globalblocking-whitelist-logentry';
$wgLogActions['gblblock/dwhitelist'] = 'globalblocking-dewhitelist-logentry'; // Stupid logging table doesn't like >16 chars
$wgLogActions['gblblock/modify'] = 'globalblocking-modify-logentry';
$wgActionFilteredLogs['gblblock'] = array(
	'gblock' => array( 'gblock', 'gblock2' ),
	'gunblock' => array( 'gunblock' ),
	'modify' => array( 'modify' ),
	'whitelist' => array( 'whitelist' ),
	'dwhitelist' => array( 'dwhitelist' )
);

## Permissions
$wgGroupPermissions['steward']['globalblock'] = true;
$wgGroupPermissions['sysop']['globalblock-whitelist'] = true;
$wgAvailableRights[] = 'globalblock';
$wgAvailableRights[] = 'globalblock-whitelist';
$wgAvailableRights[] = 'globalblock-exempt';

## CONFIGURATION
/**
 * Database name you keep global blocking data in.
 *
 * If this is not on the primary database connection, don't forget
 * to also set up $wgDBservers to have an entry with a groupLoads
 * setting for the 'globalblocking' group.
 */
$wgGlobalBlockingDatabase = 'globalblocking';

/**
 * Override $wgGlobalBlockingDatabase for Wikimedia Jenkins.
 */
if( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI ) {
	$wgGlobalBlockingDatabase = $wgDBname;
}

/**
 * Whether to respect global blocks on this wiki. This is used so that
 * global blocks can be set one one wiki, but not actually applied there
 * (i.e. so people can contest them on that wiki.
 */
$wgApplyGlobalBlocks = true;

/**
 * Whether to block a request if an IP in the XFF is blocked
 */
$wgGlobalBlockingBlockXFF = true;
