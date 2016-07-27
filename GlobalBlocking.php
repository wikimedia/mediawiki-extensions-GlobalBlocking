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
	die( 'This version of the GlobalBlocking extension requires MediaWiki 1.25+' );
}
