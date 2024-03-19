<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use ManualLogEntry;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Title\Title;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentity;
use StatusValue;
use Wikimedia\Rdbms\IConnectionProvider;

class GlobalBlockLocalStatusManager {

	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockingAllowGlobalAccountBlocks',
	];

	private ServiceOptions $options;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingBlockPurger $globalBlockingBlockPurger;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private IConnectionProvider $localDbProvider;
	private CentralIdLookup $centralIdLookup;

	public function __construct(
		ServiceOptions $options,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingBlockPurger $globalBlockingBlockPurger,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		IConnectionProvider $localDbProvider,
		CentralIdLookup $centralIdLookup
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingBlockPurger = $globalBlockingBlockPurger;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->localDbProvider = $localDbProvider;
		$this->centralIdLookup = $centralIdLookup;
	}

	/**
	 * Disable a global block applying to users on a given wiki.
	 *
	 * @param string $target The specific target of the block being disabled on this wiki. Can be an IP or IP range.
	 * @param string $reason The reason for locally disabling the block.
	 * @param UserIdentity $performer The user who is locally disabling the block. The caller of this method is
	 *     responsible for determining if the performer has the necessary rights to perform the action.
	 * @param string|false $wikiId The wiki where the block should be modified. Use false for the local wiki.
	 * @return StatusValue
	 */
	public function locallyDisableBlock(
		string $target, string $reason, UserIdentity $performer, $wikiId = false
	): StatusValue {
		// We need to purge expired blocks so we can be sure that the block we are locally disabling isn't
		// already expired.
		$this->globalBlockingBlockPurger->purgeExpiredBlocks();

		// Check that a block exists on the given $target.
		$globalBlockId = $this->globalBlockLookup->getGlobalBlockId( $target );
		if ( !$globalBlockId ) {
			$errorMessageKey = $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ?
				'globalblocking-notblocked-new' : 'globalblocking-notblocked';
			return StatusValue::newFatal( $errorMessageKey, $target );
		}

		// Assert that the block is not already locally disabled.
		$localWhitelistInfo = $this->globalBlockLocalStatusLookup
			->getLocalWhitelistInfo( $globalBlockId, null, $wikiId );
		if ( $localWhitelistInfo !== false ) {
			return StatusValue::newFatal( 'globalblocking-whitelist-nochange', $target );
		}

		// Find the expiry of the block. This is important so that we can store it in the
		// global_block_whitelist table, which allows us to purge it when the block has expired.
		$expiry = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase()->newSelectQueryBuilder()
			->select( 'gb_expiry' )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $globalBlockId ] )
			->caller( __METHOD__ )
			->fetchField();

		$this->localDbProvider->getPrimaryDatabase( $wikiId )->newInsertQueryBuilder()
			->insertInto( 'global_block_whitelist' )
			->row( [
				'gbw_by' => $performer->getId(),
				'gbw_by_text' => $performer->getName(),
				'gbw_reason' => trim( $reason ),
				'gbw_address' => $target,
				'gbw_target_central_id' => $this->centralIdLookup
					->centralIdFromName( $target, CentralIdLookup::AUDIENCE_RAW ),
				'gbw_expiry' => $expiry,
				'gbw_id' => $globalBlockId
			] )
			->caller( __METHOD__ )
			->execute();

		$this->addLogEntry( 'whitelist', $target, $reason, $performer );
		return StatusValue::newGood( [ 'id' => $globalBlockId ] );
	}

	/**
	 * @param string $target The specific target of the block being enabled on this wiki. Can be an IP or IP range.
	 * @param string $reason The reason for enabling the block.
	 * @param UserIdentity $performer The user who is locally enabling the block. The caller of this method is
	 *    responsible for determining if the performer has the necessary rights to perform the action.
	 * @param string|false $wikiId The wiki where the block should be modified. Use false for the local wiki.
	 * @return StatusValue
	 */
	public function locallyEnableBlock(
		string $target, string $reason, UserIdentity $performer, $wikiId = false
	): StatusValue {
		// Only allow locally re-enabling a global block if the global block exists.
		$globalBlockId = $this->globalBlockLookup->getGlobalBlockId( $target );
		if ( !$globalBlockId ) {
			$errorMessageKey = $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ?
				'globalblocking-notblocked-new' : 'globalblocking-notblocked';
			return StatusValue::newFatal( $errorMessageKey, $target );
		}

		// Assert that the block is locally disabled.
		$localWhitelistInfo = $this->globalBlockLocalStatusLookup
			->getLocalWhitelistInfo( $globalBlockId, null, $wikiId );
		if ( $localWhitelistInfo === false ) {
			return StatusValue::newFatal( 'globalblocking-whitelist-nochange', $target );
		}

		// Locally re-enable the block by removing the associated global_block_whitelist row.
		$this->localDbProvider->getPrimaryDatabase( $wikiId )->newDeleteQueryBuilder()
			->deleteFrom( 'global_block_whitelist' )
			->where( [ 'gbw_id' => $globalBlockId ] )
			->caller( __METHOD__ )
			->execute();

		$this->addLogEntry( 'dwhitelist', $target, $reason, $performer );
		return StatusValue::newGood( [ 'id' => $globalBlockId ] );
	}

	/**
	 * Add a log entry for the change of the local status of a global block.
	 *
	 * @param string $action either 'whitelist' or 'dwhitelist'
	 * @param string $target Target IP, range, or username.
	 * @param string $reason The reason for the local status change.
	 */
	protected function addLogEntry( string $action, string $target, string $reason, UserIdentity $performer ) {
		$logEntry = new ManualLogEntry( 'gblblock', $action );
		$logEntry->setTarget( Title::makeTitleSafe( NS_USER, $target ) );
		$logEntry->setComment( $reason );
		$logEntry->setPerformer( $performer );
		$logId = $logEntry->insert();
		$logEntry->publish( $logId );
	}
}
