<?php

namespace MediaWiki\Extension\GlobalBlocking\Hooks;

use MediaWiki\Extension\GlobalBlocking\GlobalBlock;

interface GlobalBlockingGetRetroactiveAutoblockIPsHook {
	/**
	 * Handle this hook to provide a list of IPs to retroactively globally autoblock for a given user target.
	 * Similar to the {@link PerformRetroactiveAutoblockHook} hook, but the caller is not expected to perform
	 * the autoblocks and the autoblocks will be global.
	 *
	 * @since 1.43
	 * @param GlobalBlock $globalBlock The global block that we are performing retroactive global autoblocks for
	 * @param int $limit The maximum number of IPs that will be retroactively autoblocked
	 * @param string[] &$ips The list of IPs to retroactively autoblock. If more than $limit IPs are provided, then
	 *   only the first $limit IPs will be globally autoblocked.
	 * @return bool|void True or no return value to continue or false to abort
	 * @codeCoverageIgnore Cannot be annotated as covered.
	 */
	public function onGlobalBlockingGetRetroactiveAutoblockIPs( GlobalBlock $globalBlock, int $limit, array &$ips );
}
