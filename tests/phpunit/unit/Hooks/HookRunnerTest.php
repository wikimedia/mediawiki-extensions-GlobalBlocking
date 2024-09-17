<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Unit\Hooks;

use MediaWiki\Extension\GlobalBlocking\Hooks\HookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\Hooks\HookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {
	public static function provideHookRunners() {
		yield HookRunner::class => [ HookRunner::class ];
	}
}
