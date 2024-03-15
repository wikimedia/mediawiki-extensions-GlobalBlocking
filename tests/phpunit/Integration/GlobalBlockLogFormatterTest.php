<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use LogFormatterTestCase;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use UnexpectedValueException;

/**
 * @covers \MediaWiki\Extension\GlobalBlocking\GlobalBlockLogFormatter
 * @group Database
 */
class GlobalBlockLogFormatterTest extends LogFormatterTestCase {

	use MockAuthorityTrait;

	public static function provideLogDatabaseRows(): array {
		return [
			'Local disable entry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'whitelist', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/20', 'namespace' => NS_USER, 'params' => [],
				],
				'extra' => [
					'text' => 'Sysop disabled the global block on 1.2.3.4/20 locally',
					'api' => [],
				],
			],
			'Local re-enable entry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'dwhitelist', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/20', 'namespace' => NS_USER, 'params' => [],
				],
				'extra' => [
					'text' => 'Sysop re-enabled the global block on 1.2.3.4/20 locally',
					'api' => [],
				],
			],
			'Global unblock on IP range' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gunblock', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/24', 'namespace' => NS_USER, 'params' => [],
				],
				'extra' => [
					'text' => 'Sysop removed the global block on 1.2.3.4/24',
					'api' => [],
				],
			],
			'Global unblock on user' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gunblock', 'user_text' => 'Sysop',
					'title' => 'Test-globally-unblocked', 'namespace' => NS_USER, 'params' => [],
				],
				'extra' => [
					'text' => 'Sysop removed the global block on Test-globally-unblocked',
					'api' => [],
				],
			],
			'Global block on IP range with non-infinite expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/24', 'namespace' => NS_USER,
					'params' => [ '5::expiry' => '20240504030201', '6::flags' => [ 'anon-only' ] ],
				],
				'extra' => [
					'text' => 'Sysop globally blocked 1.2.3.4/24 with an expiration time of ' .
						'03:02, 4 May 2024 (anonymous users only)',
					'api' => [ 'expiry' => '20240504030201', 'flags' => [ 'anon-only' ] ],
				],
			],
			'Global block on account with infinite expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
					'params' => [ '5::expiry' => 'infinite', '6::flags' => [] ],
				],
				'extra' => [
					'text' => 'Sysop globally blocked Test-globally-blocked with an expiration time of infinite',
					'api' => [ 'expiry' => 'infinite', 'flags' => [] ],
				],
			],
			'Legacy log for global block on IP range with non-infinite expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/24', 'namespace' => NS_USER, 'params' => [ '3 months' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.4/24 with an expiration time of 3 months',
					'api' => [ '3 months' ],
				],
			],
			'Newer legacy log for global block on IP range with non-infinite expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock2', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/24', 'namespace' => NS_USER,
					'params' => [ 'expiration 13:24, 27 February 2024', '1.2.3.4/24' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.4/24 (expiration 13:24, 27 February 2024)',
					'api' => [ 'expiration 13:24, 27 February 2024', '1.2.3.4/24' ],
				],
			],
			'Modification of global block on account with temporary expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'modify', 'user_text' => 'Sysop',
					'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
					'params' => [ '5::expiry' => '20250403020100', '6::flags' => [] ],
				],
				'extra' => [
					'text' => 'Sysop changed global block settings for Test-globally-blocked with an ' .
						'expiration time of 02:01, 3 April 2025',
					'api' => [ 'expiry' => '20250403020100', 'flags' => [] ],
				],
			],
			'Legacy modification of global block on IP with temporary expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'modify', 'user_text' => 'Sysop',
					'title' => '1.2.3.4', 'namespace' => NS_USER,
					'params' => [ 'expiration 22:05, 28 February 2024', '1.2.3.4' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop changed global block settings for 1.2.3.4 (expiration 22:05, 28 February 2024)',
					'api' => [ 'expiration 22:05, 28 February 2024', '1.2.3.4' ],
				],
			],
		];
	}

	/**
	 * @dataProvider provideLogDatabaseRows
	 */
	public function testLogDatabaseRows( $row, $extra ) {
		$this->doTestLogFormatter( $row, $extra );
	}

	/**
	 * Checks that an exception is thrown by the log formatter if a unknown action/log subtype is provided.
	 */
	public function testExceptionOnUnrecognisedLogSubtype() {
		$this->expectException( UnexpectedValueException::class );
		$this->doTestLogFormatter(
			[
				'type' => 'gblblock', 'action' => 'test', 'user_text' => 'Sysop',
				'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
				'params' => [ '5::expiry' => '20250403020100', '6::flags' => [] ],
			],
			[ 'text' => '', 'api' => [] ]
		);
	}
}
