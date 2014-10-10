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
$dir = __DIR__;
$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'GlobalBlocking',
	'author'         => 'Andrew Garrett',
	'descriptionmsg' => 'globalblocking-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:GlobalBlocking',
);

$wgMessagesDirs['GlobalBlocking'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['GlobalBlocking'] = "$dir/GlobalBlocking.i18n.php";
$wgExtensionMessagesFiles['GlobalBlockingAlias'] = "$dir/GlobalBlocking.alias.php";

$wgHooks['getUserPermissionsErrorsExpensive'][] = 'GlobalBlockingHooks::onGetUserPermissionsErrorsExpensive';
$wgHooks['UserIsBlockedGlobally'][] = 'GlobalBlockingHooks::onUserIsBlockedGlobally';
$wgHooks['SpecialPasswordResetOnSubmit'][] = 'GlobalBlockingHooks::onSpecialPasswordResetOnSubmit';
$wgHooks['OtherBlockLogLink'][] = 'GlobalBlockingHooks::onOtherBlockLogLink';
$wgHooks['SpecialContributionsBeforeMainOutput'][] = 'GlobalBlockingHooks::onSpecialContributionsBeforeMainOutput';
$wgHooks['UserMergeAccountFields'][] = 'GlobalBlockingHooks::onUserMergeAccountFields';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'GlobalBlockingHooks::onLoadExtensionSchemaUpdates';

$wgAutoloadClasses['SpecialGlobalBlock'] = "$dir/includes/specials/SpecialGlobalBlock.php";
$wgSpecialPages['GlobalBlock'] = 'SpecialGlobalBlock';
$wgAutoloadClasses['SpecialGlobalBlockList'] = "$dir/includes/specials/SpecialGlobalBlockList.php";
$wgAutoloadClasses['GlobalBlockListPager'] = "$dir/includes/specials/SpecialGlobalBlockList.php";
$wgSpecialPages['GlobalBlockList'] = 'SpecialGlobalBlockList';
$wgAutoloadClasses['SpecialGlobalBlockStatus'] = "$dir/includes/specials/SpecialGlobalBlockStatus.php";
$wgSpecialPages['GlobalBlockStatus'] = 'SpecialGlobalBlockStatus';
$wgAutoloadClasses['SpecialRemoveGlobalBlock'] = "$dir/includes/specials/SpecialRemoveGlobalBlock.php";
$wgSpecialPages['RemoveGlobalBlock'] = 'SpecialRemoveGlobalBlock';
$wgAutoloadClasses['ApiQueryGlobalBlocks'] = "$dir/includes/api/ApiQueryGlobalBlocks.php";
$wgAPIListModules['globalblocks'] = 'ApiQueryGlobalBlocks';
$wgAutoloadClasses['ApiGlobalBlock'] = "$dir/includes/api/ApiGlobalBlock.php";
$wgAPIModules['globalblock'] = 'ApiGlobalBlock';

$wgAutoloadClasses['GlobalBlocking'] = "$dir/includes/GlobalBlocking.class.php";
$wgAutoloadClasses['GlobalBlockingHooks'] = "$dir/includes/GlobalBlockingHooks.php";

$wgSpecialPageGroups['GlobalBlock'] = 'users';
$wgSpecialPageGroups['GlobalBlockList'] = 'users';
$wgSpecialPageGroups['GlobalBlockStatus'] = 'users';
$wgSpecialPageGroups['RemoveGlobalBlock'] = 'users';

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
