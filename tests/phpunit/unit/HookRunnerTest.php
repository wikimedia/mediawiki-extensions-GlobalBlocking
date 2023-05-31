<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit;

use MediaWiki\Extension\GlobalBlocking\Hook\GlobalBlockingHookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Hook\GlobalBlockingHookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners() {
		yield GlobalBlockingHookRunner::class => [ GlobalBlockingHookRunner::class ];
	}
}
