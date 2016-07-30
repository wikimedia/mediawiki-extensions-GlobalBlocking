<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'GlobalBlocking' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['GlobalBlocking'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['GlobalBlockingAlias'] = __DIR__ . '/GlobalBlocking.alias.php';
	/* wfWarn(
		'Deprecated PHP entry point used for GlobalBlocking extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the GlobalBlocking extension requires MediaWiki 1.28+' );
}

// Global declarations and documentation kept for IDEs and PHP documentors.
// This code is never executed.

/**
 * Database name you keep global blocking data in.
 *
 * If this is not on the primary database connection, don't forget
 * to also set up $wgDBservers to have an entry with a groupLoads
 * setting for the 'globalblocking' group.
 */
$wgGlobalBlockingDatabase = 'globalblocking';

/**
 * Whether to respect global blocks on this wiki. This is used so that
 * global blocks can be set one one wiki, but not actually applied there
 * (i.e. so people can contest them on that wiki).
 */
$wgApplyGlobalBlocks = true;

/**
 * Whether to block a request if an IP in the XFF is blocked
 */
$wgGlobalBlockingBlockXFF = true;
