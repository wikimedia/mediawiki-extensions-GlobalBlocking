<?php

namespace MediaWiki\Extension\GlobalBlocking\Maintenance;

use Maintenance;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockingServices;
use MediaWiki\User\User;
use Wikimedia\IPUtils;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

class GloballyBlock extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'GlobalBlocking' );

		$this->addDescription(
			'Globally blocks (or unblocks) a list of IPs, IP ranges, and/or usernames. ' .
			"Specify the block targets using either STDIN by passing a filename as the first argument.\n\n" .
			'By default, all IP ranges and IPs are hard blocked and all blocks are set to deny account creation. ' .
			'Use the options to override the defaults.'
		);

		$this->addArg( 'file', 'File with a list of IPs, IP ranges, and/or usernames to globally block', false );
		$this->addOption( 'performer', 'User to make the global blocks', false, true );
		$this->addOption( 'reason', 'Reason for the blocks', false, true );
		$this->addOption( 'reblock', 'Should the users already globally blocked have their block modified' );
		$this->addOption( 'unblock', 'If the targets should be unblocked instead of blocked' );
		$this->addOption( 'expiry', 'Expiry of the global blocks', false, true );
		$this->addOption( 'allow-createaccount', 'Set the blocks to allow account creation' );
		$this->addOption(
			'disable-hardblock',
			"Don't block logged in accounts from a globally blocked IP address (will still block temporary accounts)"
		);
	}

	public function execute() {
		// Parse the performer given for the global (un)blocks
		$performerName = $this->getOption( 'performer', false );
		if ( $performerName ) {
			$performer = $this->getServiceContainer()->getUserFactory()->newFromName( $performerName );
		} else {
			$performer = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		}

		if ( $performer === null ) {
			$this->fatalError( "Unable to parse performer's username" );
		}

		// Get the list of users to be globally (un)blocked
		if ( $this->hasArg( 'file' ) ) {
			$file = fopen( $this->getArg( 'file' ), 'r' );
		} else {
			$file = $this->getStdin();
		}

		if ( !$file ) {
			$this->fatalError( 'Unable to read file, exiting' );
		}

		// Generate the global block options from the options passed to the maintenance script
		$options = [];
		if ( $this->hasOption( 'reblock' ) ) {
			$options[] = 'modify';
		}

		if ( $this->hasOption( 'allow-createaccount' ) ) {
			$options[] = 'allow-account-creation';
		}

		$unblock = $this->hasOption( 'unblock' );
		$action = $unblock ? 'unblocking' : 'blocking';

		$reason = $this->getOption( 'reason', '' );
		$expiry = $this->getOption( 'expiry', 'indefinite' );

		$globalBlockManager = GlobalBlockingServices::wrap( $this->getServiceContainer() )->getGlobalBlockManager();

		// Start globally (un)blocking the target users
		while ( !feof( $file ) ) {
			$line = trim( fgets( $file ) );
			if ( $line == '' ) {
				continue;
			}

			if ( $unblock ) {
				$status = $globalBlockManager->unblock( $line, $reason, $performer );
			} else {
				$optionsForThisBlock = $options;
				// Only apply the 'anon-only' flag if the target is an IP, otherwise GlobalBlockManager::block will
				// not block user accounts.
				if ( IPUtils::isIPAddress( $line ) && $this->hasOption( 'disable-hardblock' ) ) {
					$optionsForThisBlock[] = 'anon-only';
				}
				$status = $globalBlockManager->block( $line, $reason, $expiry, $performer, $optionsForThisBlock );
			}

			if ( !$status->isOK() ) {
				$errorTexts = [];
				foreach ( $status->getMessages() as $error ) {
					$errorTexts[] = wfMessage( $error )->text();
				}
				$text = implode( ', ', $errorTexts );
				$this->output( "Globally $action '$line' failed ($text).\n" );
			} else {
				$this->output( "Globally $action '$line' succeeded.\n" );
			}
		}
	}
}

// @codeCoverageIgnoreStart
$maintClass = GloballyBlock::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
