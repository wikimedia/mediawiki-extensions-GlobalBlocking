<?php

namespace MediaWiki\Extension\GlobalBlocking\Test\Integration;

use CentralAuthTestUser;
use LogFormatter;
use LogFormatterTestCase;
use LogPage;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\WikiMap\WikiMap;
use TestUserRegistry;
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
			'Local disable entry when specifying a block ID' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'whitelist', 'user_text' => 'Sysop',
					'title' => '#123', 'namespace' => NS_USER, 'params' => [],
				],
				'extra' => [
					'text' => 'Sysop disabled the global block on #123 locally',
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
						'03:02, 4 May 2024 (anonymous users only, account creation disabled)',
					'api' => [ 'expiry' => '20240504030201', 'flags' => [ 'anon-only' ] ],
				],
			],
			'Global block on account with infinite expiry with account creation enabled and autoblocks enabled' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
					'params' => [
						'5::expiry' => 'infinite', '6::flags' => [ 'allow-account-creation', 'enable-autoblock' ],
					],
				],
				'extra' => [
					'text' => 'Sysop globally blocked Test-globally-blocked with an expiration time of infinite',
					'api' => [ 'expiry' => 'infinite', 'flags' => [ 'allow-account-creation', 'enable-autoblock' ] ],
				],
			],
			'Global block on account with infinite expiry with account creation enabled' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
					'params' => [ '5::expiry' => 'infinite', '6::flags' => [ 'allow-account-creation' ] ],
				],
				'extra' => [
					'text' => 'Sysop globally blocked Test-globally-blocked with an expiration time of infinite ' .
						'(autoblock disabled)',
					'api' => [ 'expiry' => 'infinite', 'flags' => [ 'allow-account-creation' ] ],
				],
			],
			'Legacy log for global block on IP range with non-infinite expiry' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => '1.2.3.4/24', 'namespace' => NS_USER, 'params' => [ '3 months' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.4/24 with an expiration time of 3 months ' .
						'(account creation disabled, autoblock disabled)',
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
					'text' => 'Sysop globally blocked 1.2.3.4/24 (expiration 13:24, 27 February 2024, account ' .
						'creation disabled, autoblock disabled)',
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
						'expiration time of 02:01, 3 April 2025 (account creation disabled, autoblock disabled)',
					'api' => [ 'expiry' => '20250403020100', 'flags' => [] ],
				],
			],
			'Modification of global block on account with temporary expiry with account creation enabled' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'modify', 'user_text' => 'Sysop',
					'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
					'params' => [ '5::expiry' => '20250403020100', '6::flags' => [ 'allow-account-creation' ] ],
				],
				'extra' => [
					'text' => 'Sysop changed global block settings for Test-globally-blocked with an ' .
						'expiration time of 02:01, 3 April 2025 (autoblock disabled)',
					'api' => [ 'expiry' => '20250403020100', 'flags' => [ 'allow-account-creation' ] ],
				],
			],
			'Modification of global block on account with temporary expiry with account creation enabled ' .
				'and autoblocks enabled' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'modify', 'user_text' => 'Sysop',
					'title' => 'Test-globally-blocked', 'namespace' => NS_USER,
					'params' => [
						'5::expiry' => '20250403020100',
						'6::flags' => [ 'allow-account-creation', 'enable-autoblock' ],
					],
				],
				'extra' => [
					'text' => 'Sysop changed global block settings for Test-globally-blocked with an ' .
						'expiration time of 02:01, 3 April 2025',
					'api' => [
						'expiry' => '20250403020100',
						'flags' => [ 'allow-account-creation', 'enable-autoblock' ],
					],
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
					'text' => 'Sysop changed global block settings for 1.2.3.4 (expiration 22:05, 28 February 2024, ' .
						'account creation disabled, autoblock disabled)',
					'api' => [ 'expiration 22:05, 28 February 2024', '1.2.3.4' ],
				],
			],
			'Legacy log entry with title as Special:Contributions (pre-2010 logs)' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Contributions/1.2.3.4', 'namespace' => NS_SPECIAL,
					'params' => [ '31hours' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.4 with an expiration time of 31hours (account creation ' .
						'disabled, autoblock disabled)',
					'api' => [ '31hours' ],
				],
			],
			'Legacy log entry with title as Special:Contributions and anonymous only flag (pre-2010 logs)' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Contributions/1.2.3.4', 'namespace' => NS_SPECIAL,
					'params' => [ '31hours', 'anonymous only' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.4 with an expiration time of 31hours (anonymous only, ' .
						'account creation disabled, autoblock disabled)',
					'api' => [ '31hours', 'anonymous only' ],
				],
			],
			'Legacy log entry with title as Special:Contributions and anon-only flag (pre-2010 logs)' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Contributions/1.2.3.4', 'namespace' => NS_SPECIAL,
					'params' => [ '31hours', 'anon-only' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.4 with an expiration time of 31hours ' .
						'(anonymous users only, account creation disabled, autoblock disabled)',
					'api' => [ '31hours', 'anon-only' ],
				],
			],
			'Legacy log entry with title as Special:Contributions for IP range (pre-2010 logs)' => [
				'row' => [
					'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
					'title' => 'Contributions/1.2.3.0/24', 'namespace' => NS_SPECIAL,
					'params' => [ '31hours', 'anon-only' ],
				],
				'extra' => [
					'legacy' => true,
					'text' => 'Sysop globally blocked 1.2.3.0/24 with an expiration time of 31hours ' .
						'(anonymous users only, account creation disabled, autoblock disabled)',
					'api' => [ '31hours', 'anon-only' ],
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

	/** @dataProvider provideLogDatabaseRowsForHiddenAction */
	public function testLogDatabaseRowsForHiddenAction(
		$logViewerHasHideUser,
		$actionIsHidden,
		$audience,
		$shouldShowAction
	) {
		$targetUser = $this->getMutableTestUser()->getUser();
		if ( $logViewerHasHideUser ) {
			$logViewAuthority = $this->mockRegisteredAuthorityWithPermissions( [
				'viewsuppressed', 'globalblock-whitelist'
			] );
		} else {
			$logViewAuthority = $this->mockRegisteredAuthorityWithoutPermissions( [
				'suppressrevision', 'viewsuppressed'
			] );
		}

		// Don't use doTestLogFormatter() since it overrides every service that
		// accesses the database and prevents correct loading of the block.
		$row = $this->expandDatabaseRow(
			[
				'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
				'title' => $targetUser->getName(), 'namespace' => NS_USER,
				'params' => [ '5::expiry' => 'infinite', '6::flags' => [] ],
				'deleted' => $actionIsHidden ? LogPage::DELETED_ACTION | LogPage::DELETED_RESTRICTED : 0,
			],
			false
		);
		$formatter = $this->getServiceContainer()->getLogFormatterFactory()->newFromRow( $row );
		$formatter->context->setAuthority( $logViewAuthority );
		$formatter->setAudience( $audience );
		if ( $shouldShowAction ) {
			$expectedName = $targetUser->getName();
			$this->assertEquals(
				"Sysop globally blocked $expectedName with an expiration time of infinite " .
					'(account creation disabled, autoblock disabled)',
				trim( strip_tags( $formatter->getActionText() ) ),
				'Action text is equal to expected text.'
			);
			$this->assertNotSame(
				'',
				$formatter->getActionLinks(),
				'Action links should not be empty if the user is not hidden.'
			);
		} else {
			$this->assertSame(
				'Sysop (log details removed)',
				trim( strip_tags( $formatter->getActionText() ) ),
				'Action text should be hidden because the action text was deleted.'
			);
			$this->assertSame(
				'',
				$formatter->getActionLinks(),
				'Action links should be empty if the action text was deleted.'
			);
		}
	}

	public static function provideLogDatabaseRowsForHiddenAction() {
		return [
			'User has hideuser, log is deleted, audience is public' => [ true, true, LogFormatter::FOR_PUBLIC, false ],
			'User has hideuser, log is deleted, audience is for this user' => [
				true, true, LogFormatter::FOR_THIS_USER, true,
			],
			'User has hideuser, log is not deleted' => [ true, false, LogFormatter::FOR_THIS_USER, true ],
			'User does not have hideuser, log is deleted' => [ false, true, LogFormatter::FOR_THIS_USER, false ],
		];
	}

	/** @dataProvider provideLogDatabaseRowsForHiddenUser */
	public function testLogDatabaseRowsForHiddenUser( $logViewerHasSuppress ) {
		$targetUser = $this->getMutableTestUser()->getUser();
		$blockingUser = $this->getTestUser( [ 'sysop', 'suppress' ] )->getUser();
		if ( $logViewerHasSuppress ) {
			$logViewAuthority = $this->mockRegisteredAuthorityWithPermissions(
				[ 'hideuser', 'globalblock-whitelist' ]
			);
		} else {
			$logViewAuthority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'hideuser' ] );
		}
		$blockStatus = $this->getServiceContainer()->getBlockUserFactory()
			->newBlockUser(
				$targetUser, $blockingUser, 'infinity',
				'block to hide the test user', [ 'isHideUser' => true ]
			)->placeBlock();
		$this->assertStatusGood( $blockStatus );

		if ( $logViewerHasSuppress ) {
			$expectedName = $targetUser->getName();
		} else {
			$expectedName = wfMessage( 'rev-deleted-user' )->text();
		}

		// Don't use doTestLogFormatter() since it overrides every service that
		// accesses the database and prevents correct loading of the block.
		$row = $this->expandDatabaseRow(
			[
				'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
				'title' => $targetUser->getName(), 'namespace' => NS_USER,
				'params' => [ '5::expiry' => 'infinite', '6::flags' => [] ],
			],
			false
		);
		$formatter = $this->getServiceContainer()->getLogFormatterFactory()->newFromRow( $row );
		$formatter->context->setAuthority( $logViewAuthority );
		$this->assertEquals(
			"Sysop globally blocked $expectedName with an expiration time of infinite " .
				'(account creation disabled, autoblock disabled)',
			trim( strip_tags( $formatter->getActionText() ) ),
			'Action text is equal to expected text'
		);
		if ( $logViewerHasSuppress ) {
			$this->assertNotSame(
				'',
				$formatter->getActionLinks(),
				'Action links should not be empty if the user is not hidden'
			);
		} else {
			$this->assertSame(
				'',
				$formatter->getActionLinks(),
				'Action links should be empty if the user is hidden'
			);
		}
	}

	public static function provideLogDatabaseRowsForHiddenUser() {
		return [
			'User does not have suppress group' => [ false ],
			'User has suppress group' => [ true ]
		];
	}

	/** @dataProvider provideLogDatabaseRowsForCentrallyHiddenUser */
	public function testLogDatabaseRowsForCentrallyHiddenUser(
		$centralUserExists,
		$logViewerHasSuppress,
		$shouldShowName
	) {
		$this->markTestSkippedIfExtensionNotLoaded( 'CentralAuth' );
		if ( $logViewerHasSuppress ) {
			$logViewAuthority = $this->mockRegisteredAuthorityWithPermissions( [
				'centralauth-suppress', 'globalblock-whitelist'
			] );
		} else {
			$logViewAuthority = $this->mockRegisteredAuthorityWithoutPermissions( [ 'centralauth-suppress' ] );
		}
		// We need a new username for each test to avoid conflicts with other data providers.
		$targetUsername = 'GloballyHiddenUser' . TestUserRegistry::getNextId();
		if ( $centralUserExists ) {
			$targetUser = new CentralAuthTestUser(
				$targetUsername, 'GUP@ssword',
				[
					'gu_id' => '3003',
					'gu_hidden_level' => CentralAuthUser::HIDDEN_LEVEL_SUPPRESSED,
				],
				[ [ WikiMap::getCurrentWikiId(), 'primary' ] ],
				false
			);
			$targetUser->save( $this->getDb() );
		}

		if ( $shouldShowName ) {
			$expectedName = $targetUsername;
		} else {
			$expectedName = wfMessage( 'rev-deleted-user' )->text();
		}

		// Don't use doTestLogFormatter() since it overrides every service that
		// accesses the database and prevents correct loading of the block.
		$row = $this->expandDatabaseRow(
			[
				'type' => 'gblblock', 'action' => 'gblock', 'user_text' => 'Sysop',
				'title' => $targetUsername, 'namespace' => NS_USER,
				'params' => [ '5::expiry' => 'infinite', '6::flags' => [] ],
			],
			false
		);
		$formatter = $this->getServiceContainer()->getLogFormatterFactory()->newFromRow( $row );
		$formatter->context->setAuthority( $logViewAuthority );
		$this->assertEquals(
			"Sysop globally blocked $expectedName with an expiration time of infinite " .
				'(account creation disabled, autoblock disabled)',
			trim( strip_tags( $formatter->getActionText() ) ),
			'Action text is equal to expected text'
		);
		if ( $shouldShowName ) {
			$this->assertNotSame(
				'',
				$formatter->getActionLinks(),
				'Action links should not be empty if the user is not hidden'
			);
		} else {
			$this->assertSame(
				'',
				$formatter->getActionLinks(),
				'Action links should be empty if the user is hidden'
			);
		}
	}

	public static function provideLogDatabaseRowsForCentrallyHiddenUser() {
		return [
			'Central user does not exist' => [
				// Does a CentralAuth user exist with the username of the target
				false,
				// Does the log viewer have the steward group
				true,
				// Should the username be shown to the log viewer
				true,
			],
			'User does not have steward group' => [ true, false, false ],
			'User has steward group' => [ true, true, true ]
		];
	}
}
