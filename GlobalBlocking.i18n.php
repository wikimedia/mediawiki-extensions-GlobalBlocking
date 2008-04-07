<?php
/**
 * Internationalisation file for extension GlobalBlocking.
 *
 * @addtogroup Extensions
 */

$messages = array();

/** English
 * @author Andrew Garrett
 */
$messages['en'] = array(
	'globalblocking-block' => 'Globally block an IP address',
	'globalblocking-expiry-options' => '-',
	'globalblocking-block-intro' => 'You can use this page to block an IP address on all wikis.',
	'globalblocking-block-reason' => 'Reason for this block:',
	'globalblocking-block-expiry' => 'Block expiry:',
	'globalblocking-block-expiry-other' => 'Other expiry time',
	'globalblocking-block-expiry-otherfield' => 'Other time:',
	'globalblocking-block-legend' => 'Block a user globally',
	'globalblocking-block-options' => 'Options',
	'globalblocking-block-errors' => "The block was unsuccessful, because: \$1",
	'globalblocking-block-ipinvalid' => 'The IP address ($1) you entered is invalid.
Please note that you cannot enter a user name!',
	'globalblocking-block-expiryinvalid' => 'The expiry you entered ($1) is invalid.',
	'globalblocking-block-submit' => 'Block this IP address globally',
	'globalblocking-block-success' => 'The IP address $1 has been successfully blocked on all Wikimedia projects.
You may wish to consult the [[Special:Globalblocklist|list of global blocks]].',
	'globalblocking-block-successsub' => 'Global block successful',
	'globalblocking-list' => 'List of globally blocked IP addresses',
	'globalblocking-search-legend' => 'Search for a global block',
	'globalblocking-search-ip' => 'IP Address:',
	'globalblocking-search-submit' => 'Search for blocks',
	'globalblocking-list-ipinvalid' => 'The IP address you searched for ($1) is invalid.
Please enter a valid IP address.',
	'globalblocking-search-errors' => "Your search was unsuccessful, because:\n\$1",
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globally blocked '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expiry $1',
	'globalblocking-list-anononly' => 'anon-only',
	'globalblocking-list-unblock' => 'unblock',

	'globalblocking-unblock-ipinvalid' => 'The IP address ($1) you entered is invalid.
Please note that you cannot enter a user name!',
	'globalblocking-unblock-legend' => 'Remove a global block',
	'globalblocking-unblock-ipaddress' => 'IP Address:',
	'globalblocking-unblock-submit' => 'Remove global block',
	'globalblocking-unblock-reason' => 'Reason:',
	'globalblocking-unblock-notblocked' => 'The IP address ($1) you entered is not globally blocked.',
	'globalblocking-unblock-unblocked' => "You have successfully removed the global block #$2 on the IP address '''$1'''",
	'globalblocking-unblock-errors' => "You cannot remove a global block for that IP address, because:\n\$1",
	'globalblocking-unblock-successsub' => 'Global block successfully removed',

	'globalblocking-blocked' => "Your IP address has been blocked on all Wikimedia wikis by '''$1''' (''$2'').
The reason given was ''\"$3\"''. The block's expiry is ''$4''.",

	'globalblocking-logpage' => 'Global block log',
	'globalblocking-block-logentry' => 'globally blocked [[$1]] with an expiry time of $2 ($3)',
	'globalblocking-unblock-logentry' => 'removed global block on [[$1]]',

	'globalblocklist' => 'List of globally blocked IP addresses',
	'globalblock' => 'Globally block an IP address',
);
