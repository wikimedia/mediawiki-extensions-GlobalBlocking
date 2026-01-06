<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use InvalidArgumentException;
use MediaWiki\Block\BlockUser;
use MediaWiki\Block\BlockUserFactory;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlockStatus;
use MediaWiki\Extension\GlobalBlocking\Hooks\HookRunner;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Language\RawMessage;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\MainConfigNames;
use MediaWiki\Permissions\Authority;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use Psr\Log\LoggerInterface;
use RuntimeException;
use StatusValue;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\RawSQLValue;

/**
 * A service for creating, updating, and removing global blocks.
 *
 * @since 1.42
 */
class GlobalBlockManager {

	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockingCIDRLimit',
		'GlobalBlockingAutoblockExpiry',
		'GlobalBlockingMaximumIPsToRetroactivelyAutoblock',
		MainConfigNames::EnableMultiBlocks,
	];

	private readonly HookRunner $hookRunner;

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly GlobalBlockingBlockPurger $globalBlockingBlockPurger,
		private readonly GlobalBlockLookup $globalBlockLookup,
		private readonly GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		private readonly GlobalBlockingGlobalAutoblockExemptionListProvider $globalAutoblockExemptionListProvider,
		private readonly CentralIdLookup $centralIdLookup,
		private readonly UserFactory $userFactory,
		private readonly LoggerInterface $logger,
		HookContainer $hookContainer,
		private readonly DatabaseBlockStore $localBlockStore,
		private readonly BlockUserFactory $localBlockUserFactory,
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->hookRunner = new HookRunner( $hookContainer );
	}

	/**
	 * Insert or update a global block with the properties specified in $data.
	 *
	 * @param array $data Attributes to be set for the block.
	 *    Required: target (string), targetForDisplay (string), targetCentralId (int), rangeStart (string),
	 *       rangeEnd (string), byCentralId (int), byWiki (string), reason (string), timestamp (any valid timestamp),
	 *       expiry (any valid timestamp or infinity), anonOnly (bool), allowAccountCreation (bool),
	 *       enableAutoblock (bool), blockEmail (bool)
	 *    Optional: parentBlockId (int, default 0), existingBlockId (int, default 0)
	 * @return GlobalBlockStatus
	 */
	private function insertBlockAfterChecks( array $data ): GlobalBlockStatus {
		$dbw = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		$row = [
			'gb_address' => $data['target'],
			'gb_target_central_id' => $data['targetCentralId'],
			'gb_by_central_id' => $data['byCentralId'],
			'gb_by_wiki' => $data['byWiki'],
			'gb_reason' => $data['reason'],
			'gb_timestamp' => $dbw->timestamp( $data['timestamp'] ),
			'gb_anon_only' => $data['anonOnly'],
			'gb_create_account' => !$data['allowAccountCreation'],
			'gb_block_email' => $data['blockEmail'],
			'gb_expiry' => $dbw->encodeExpiry( $data['expiry'] ),
			'gb_range_start' => $data['rangeStart'],
			'gb_range_end' => $data['rangeEnd'],
			'gb_autoblock_parent_id' => $data['parentBlockId'] ?? 0,
			'gb_enable_autoblock' => $data['enableAutoblock'],
		];

		$blockId = 0;
		if ( $data['existingBlockId'] ?? 0 ) {
			$dbw->newUpdateQueryBuilder()
				->update( 'globalblocks' )
				->set( $row )
				->where( [ 'gb_id' => $data['existingBlockId'] ] )
				->caller( __METHOD__ )
				->execute();
			if ( $dbw->affectedRows() ) {
				$blockId = $data['existingBlockId'];
			}
		} else {
			$dbw->newInsertQueryBuilder()
				->insertInto( 'globalblocks' )
				->ignore()
				->row( $row )
				->caller( __METHOD__ )
				->execute();
			if ( $dbw->affectedRows() ) {
				$blockId = $dbw->insertId();
			}
		}

		if ( !$blockId ) {
			// Race condition?
			return GlobalBlockStatus::newFatal( 'globalblocking-block-failure', $data['targetForDisplay'] );
		}

		// Delete any corresponding global autoblocks if autoblocking is disabled for an existing block, as it may
		// have been enabled previously.
		if ( ( $data['existingBlockId'] ?? 0 ) && !$data['enableAutoblock'] ) {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'globalblocks' )
				->where( [ 'gb_autoblock_parent_id' => $blockId ] )
				->caller( __METHOD__ )
				->execute();
		}

		// Fetch the newly inserted or updated row for use to construct a GlobalBlock object for use in autoblocks and
		// the GlobalBlockingGlobalBlockAudit hook. We need to read from primary, as the changes are likely not
		// applied to replicas yet.
		$blockRow = $dbw->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $blockId ] )
			->caller( __METHOD__ )
			->fetchRow();
		$blockObject = GlobalBlock::newFromRow( $blockRow, false );

		if ( $data['enableAutoblock'] ) {
			// If autoblocks are enabled for this block, then perform retroactive autoblocks and update existing
			// autoblock expiry times.
			if ( $data['existingBlockId'] ?? 0 ) {
				// Update corresponding global autoblock(s) if the block is modified to match the relevant settings
				// from the modified parent block.
				$dbw->newUpdateQueryBuilder()
					->update( 'globalblocks' )
					->set( [
						'gb_by_central_id' => $data['byCentralId'],
						'gb_by_wiki' => $data['byWiki'],
						'gb_create_account' => !$data['allowAccountCreation'],
						'gb_reason' => $this->globalBlockLookup->getAutoblockReason( $blockRow, false ),
						// Shorten the autoblock expiry if the parent block expiry is sooner, but don't lengthen.
						// Taken from DatabaseBlockStore::getArrayForAutoblockUpdate.
						'gb_expiry' => new RawSQLValue( $dbw->conditional(
							$dbw->expr( 'gb_expiry', '>', $dbw->encodeExpiry( $data['expiry'] ) ),
							$dbw->addQuotes( $dbw->encodeExpiry( $data['expiry'] ) ),
							'gb_expiry'
						) ),
					] )
					->where( [ 'gb_autoblock_parent_id' => $blockId ] )
					->caller( __METHOD__ )->execute();
			}

			// Sanity check that autoblocking is enabled for this block. Use GlobalBlock::isAutoblocking as it also
			// performs extra checks to determine if the block should cause autoblocks.
			if ( $blockObject->isAutoblocking() ) {
				// Fetch the list of IP addresses to retroactively autoblock, which are provided by other extensions
				// through handling the hook below (e.g. CheckUser).
				$ipsToAutoBlock = [];
				$maxIPsToAutoBlock = $this->options->get( 'GlobalBlockingMaximumIPsToRetroactivelyAutoblock' );
				$this->hookRunner->onGlobalBlockingGetRetroactiveAutoblockIPs(
					$blockObject,
					$maxIPsToAutoBlock,
					$ipsToAutoBlock
				);
				$ipsToAutoBlock = array_slice( $ipsToAutoBlock, 0, $maxIPsToAutoBlock );

				// Retroactively autoblock the provided IP addresses.
				foreach ( $ipsToAutoBlock as $ip ) {
					$this->autoblock( $blockId, $ip );
				}
			}
		}

		$this->hookRunner->onGlobalBlockingGlobalBlockAudit( $blockObject );

		return GlobalBlockStatus::newGood( [
			'id' => $blockId,
		] );
	}

	/**
	 * Block an IP address or range.
	 *
	 * @param string $target The IP address, IP range, username, or block ID which is prefixed with "#".
	 * @param string $reason The public reason to be shown in the global block log,
	 *   on the global block list, and potentially to the blocked user when they try to edit.
	 *   If $localOptions is set, it will also be used for the local block reason.
	 * @param string $expiry Any expiry that can be parsed by BlockUser::parseExpiryInput, including infinite.
	 * @param Authority|UserIdentity $blocker The user performing the block. The caller of this method is
	 *    responsible for determining if the performer has the necessary rights to perform the block.
	 * @param array $options An array of options provided as values with numeric keys. This accepts:
	 *   - 'anon-only': If set, only anonymous users will be affected by the block
	 *   - 'modify': If set, the block will be modified if it already exists. If not set,
	 *       the block will fail if it already exists.
	 *   - 'allow-account-creation': If set, the block will allow account creation. Otherwise,
	 *       the block will prevent account creation.
	 *   - 'enable-autoblock': If set, the block will cause autoblocks.
	 *   - 'block-email': If set, the block will prevent the use of Special:EmailUser.
	 * @param array|null $localOptions An array with local block options to pass through to
	 *   BlockUser, or null to skip local blocking
	 * @return GlobalBlockStatus A status object, with errors if the block failed.
	 */
	public function block(
		string $target, string $reason, string $expiry, $blocker, array $options = [],
		?array $localOptions = null
	): GlobalBlockStatus {
		if ( $blocker instanceof Authority ) {
			$blockerUser = $blocker->getUser();
		} elseif ( $blocker instanceof UserIdentity ) {
			$blockerUser = $blocker;
		} else {
			throw new InvalidArgumentException( '$blocker must be an Authority or UserIdentity' );
		}
		$expiry = BlockUser::parseExpiryInput( $expiry );
		if ( $expiry === false ) {
			return GlobalBlockStatus::newFatal( 'globalblocking-block-expiryinvalid' );
		}

		$status = $this->validateGlobalBlockTarget( $target, $blockerUser );

		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $status->getValue();

		$modify = in_array( 'modify', $options );
		$anonOnly = in_array( 'anon-only', $options );
		$allowAccountCreation = in_array( 'allow-account-creation', $options );
		$enableAutoblock = in_array( 'enable-autoblock', $options );
		$blockEmail = in_array( 'block-email', $options );

		// As we are inserting a block and therefore will be using a primary DB connection,
		// we can purge expired blocks from the primary DB.
		$this->globalBlockingBlockPurger->purgeExpiredBlocks( $data['target'] );

		if ( $anonOnly && !IPUtils::isIPAddress( $data['target'] ) ) {
			// Anon-only blocks on an account does not make any sense, so reject them.
			return GlobalBlockStatus::newFatal( 'globalblocking-block-anononly-on-account', $data['targetForDisplay'] );
		}

		if ( $enableAutoblock && !$data['targetCentralId'] ) {
			// Global blocks can only be autoblocking if they target a user.
			return GlobalBlockStatus::newFatal(
				'globalblocking-block-enable-autoblock-on-ip', $data['targetForDisplay'] );
		}

		// Look to see if this target is already globally blocked.
		$existingGlobalBlockId = $this->globalBlockLookup->getGlobalBlockId( $data['targetForLookup'], DB_PRIMARY );
		if ( !$modify && $existingGlobalBlockId ) {
			return GlobalBlockStatus::newFatal( 'globalblocking-block-alreadyblocked', $data['targetForDisplay'] );
		}

		$existingBlockRow = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase()
			->newSelectQueryBuilder()
			->select( [
				'gb_address',
				'gb_by_central_id',
				'gb_timestamp',
				'gb_autoblock_parent_id'
			] )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $existingGlobalBlockId ] )
			->caller( __METHOD__ )
			->fetchRow();

		$localBlock = null;
		if ( $existingBlockRow ) {
			// Prevent modifications of global autoblocks. This check is not performed when calling
			// ::unblock, so that autoblocks can be removed.
			if ( $existingBlockRow->gb_autoblock_parent_id ) {
				return GlobalBlockStatus::newFatal(
					'globalblocking-block-modifying-global-autoblock', $data['targetForDisplay']
				);
			}
			// If we're modifying a local block, find the block (T387730)
			if ( $localOptions !== null && $modify ) {
				$localBlock = $this->findLocalBlock(
					$existingBlockRow->gb_address,
					$existingBlockRow->gb_by_central_id,
					$existingBlockRow->gb_timestamp
				);
			}
		} else {
			// If no global block exists, set the modify flag to false so the correct log entry is created (T386235).
			if ( $modify ) {
				$modify = false;
			}
		}

		// At this point, we have validated that a block can be inserted or updated.
		$status = $this->insertBlockAfterChecks( array_merge( [
			'byCentralId' => $this->centralIdLookup->centralIdFromLocalUser( $blockerUser ),
			'byWiki' => WikiMap::getCurrentWikiId(),
			'reason' => $reason,
			'timestamp' => wfTimestampNow(),
			'expiry' => $expiry,
			'anonOnly' => $anonOnly,
			'allowAccountCreation' => $allowAccountCreation,
			'blockEmail' => $blockEmail,
			'enableAutoblock' => $enableAutoblock,
			'existingBlockId' => $existingGlobalBlockId,
		], $data ) );

		if ( !$status->isOK() ) {
			return $status;
		}

		$blockId = $status->getValue()['id'];

		// Log it.
		$logAction = $modify ? 'modify' : 'gblock';

		$logEntry = new ManualLogEntry( 'gblblock', $logAction );
		$logEntry->setPerformer( $blockerUser );
		$logEntry->setTarget( $this->getTargetForLogEntry( $target ) );
		$logEntry->setComment( $reason );

		$flags = [];
		if ( $anonOnly ) {
			$flags[] = 'anon-only';
		}
		if ( $allowAccountCreation ) {
			$flags[] = 'allow-account-creation';
		}
		if ( $enableAutoblock ) {
			$flags[] = 'enable-autoblock';
		}
		if ( $blockEmail ) {
			$flags[] = 'block-email';
		}

		// The 4th parameter is the target as plaintext used for GENDER support and is added by the log formatter.
		$logEntry->setParameters( [
			'5::expiry' => $expiry,
			// List of flags which are then converted to a comma separated localised list by the log formatter
			'6::flags' => $flags,
		] );
		$logEntry->setRelations( [ 'gb_id' => $blockId ] );
		$logId = $logEntry->insert();
		$logEntry->publish( $logId );

		// Insert or modify the local block
		if ( $localOptions !== null ) {
			if ( $blocker instanceof Authority ) {
				$blockerAuthority = $blocker;
			} else {
				$blockerAuthority = $this->userFactory->newFromUserIdentity( $blocker );
			}

			if ( $localBlock ) {
				$blockUser = $this->localBlockUserFactory->newUpdateBlock(
					$localBlock, $blockerAuthority, $expiry, $reason, $localOptions
				);
			} else {
				$blockUser = $this->localBlockUserFactory->newBlockUser(
					$data['target'], $blockerAuthority, $expiry, $reason, $localOptions
				);
			}

			$localStatus = $blockUser->placeBlock( BlockUser::CONFLICT_NEW );
			$status = $status->withLocalStatus( $localStatus );
		}

		return $status;
	}

	/**
	 * Find the local block which was created in the same action as an existing global block
	 *
	 * @param string $target
	 * @param int $performerCentralId
	 * @param string $timestamp
	 * @return DatabaseBlock|null
	 */
	private function findLocalBlock( string $target, int $performerCentralId, string $timestamp ) {
		$existingPerformer = $this->centralIdLookup->localUserFromCentralId( $performerCentralId );
		$localBlocks = $this->localBlockStore->newListFromTarget( $target, null, true );
		if ( $this->options->get( MainConfigNames::EnableMultiBlocks ) ) {
			// Find the matching block
			foreach ( $localBlocks as $localBlock ) {
				if ( $localBlock->getTargetName() === $target
					&& $localBlock->getBlocker()->equals( $existingPerformer )
					&& $this->isCloseTimestamp( $localBlock->getTimestamp(), $timestamp )
				) {
					return $localBlock;
				}
			}
			return null;
		} else {
			return $localBlocks[0] ?? null;
		}
	}

	/**
	 * Compare two MediaWiki timestamps, checking whether they are close enough
	 * to have been part of the same global block action
	 *
	 * @param string $ts1
	 * @param string $ts2
	 * @return bool
	 */
	private function isCloseTimestamp( string $ts1, string $ts2 ) {
		$ts1Unix = wfTimestamp( TS_UNIX, $ts1 );
		$ts2Unix = wfTimestamp( TS_UNIX, $ts2 );
		return abs( (int)$ts1Unix - (int)$ts2Unix ) < 60;
	}

	/**
	 * Globally autoblocks the given IP address with the provided $parentId as the parent global block.
	 *
	 * @param int $parentId The ID of the globalblocks row that is causing this autoblock
	 * @param string $ip The IP address to be autoblocked
	 * @return StatusValue
	 */
	public function autoblock( int $parentId, string $ip ): StatusValue {
		$ip = IPUtils::sanitizeIP( $ip );

		if ( !$ip || !IPUtils::isValid( $ip ) ) {
			// We shouldn't encounter this error, and this is more of a sanity check. Therefore, we shouldn't need to
			// translate this error message to save time for the translators.
			return StatusValue::newFatal( new RawMessage( 'IP provided for autoblocking is invalid.' ) );
		}

		if ( $this->globalAutoblockExemptionListProvider->isExempt( $ip ) ) {
			return StatusValue::newGood();
		}

		// We need to perform a primary lookup as the parent block may have just been inserted and we are now
		// retroactively autoblocking.
		$dbw = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		$parentBlock = $dbw->newSelectQueryBuilder()
			->select( GlobalBlockLookup::selectFields() )
			->from( 'globalblocks' )
			->where( [ 'gb_id' => $parentId ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $parentBlock === false ) {
			// The block should exist, so return a fatal as this likely indicates a bug. Also create an error log
			// to keep an eye on this.
			$this->logger->error(
				'Autoblock attempted on IP when parent block #{id} does not exist',
				[ 'id' => $parentId, 'exception' => new RuntimeException ]
			);
			return StatusValue::newFatal( 'globalblocking-notblocked-id', $parentId );
		}

		// Return if the block does not cause autoblocks.
		if ( !$parentBlock->gb_enable_autoblock ) {
			return StatusValue::newGood();
		}

		// We need to perform the lookup on primary because in the case of multiple users being globally blocked at
		// the same time, this method may be called too quickly for replication to occur.
		$globalBlocksOnIP = $dbw->newSelectQueryBuilder()
			->select( [ 'gb_autoblock_parent_id', 'gb_id', 'gb_expiry' ] )
			->from( 'globalblocks' )
			->where( [ 'gb_address' => $ip ] )
			->caller( __METHOD__ )
			->fetchResultSet();
		$parentBlockExpiry = $dbw->decodeExpiry( $parentBlock->gb_expiry );
		$timestamp = wfTimestampNow();

		// If blocks exist on the the IP to be autoblocked, then don't make a new autoblock as it would be redundant.
		if ( $globalBlocksOnIP->numRows() ) {
			foreach ( $globalBlocksOnIP as $block ) {
				if ( $block->gb_autoblock_parent_id ) {
					// Update the expiration of autoblocks on this IP if our parent block expires after the autoblock.
					// This is so that the autoblock is refreshed when a blocked user tries to use the IP again.
					$autoblockExpiry = $dbw->decodeExpiry( $block->gb_expiry );

					if ( $parentBlockExpiry !== 'infinity' && $parentBlockExpiry <= $autoblockExpiry ) {
						continue;
					}

					$dbw->newUpdateQueryBuilder()
						->update( 'globalblocks' )
						->set( [
							'gb_timestamp' => $timestamp,
							'gb_expiry' => $this->getAutoblockExpiry( $timestamp, $parentBlockExpiry ),
						] )
						->where( [ 'gb_id' => $block->gb_id ] )
						->caller( __METHOD__ )
						->execute();
				}
			}
			return StatusValue::newGood();
		}

		// We have performed all the necessary checks, and can insert a new autoblock to the globalblocks table
		// for the IP, using the parameters from the parent block where appropriate.
		return $this->insertBlockAfterChecks( [
			'target' => $ip,
			'targetForDisplay' => '',
			'targetCentralId' => 0,
			'rangeStart' => IPUtils::toHex( $ip ),
			'rangeEnd' => IPUtils::toHex( $ip ),
			'byCentralId' => $parentBlock->gb_by_central_id,
			'byWiki' => $parentBlock->gb_by_wiki,
			'reason' => $this->globalBlockLookup->getAutoblockReason( $parentBlock, false ),
			'timestamp' => $timestamp,
			'expiry' => $this->getAutoblockExpiry( $timestamp, $parentBlockExpiry ),
			// Global autoblocks should target logged-in users, like local autoblocks do.
			'anonOnly' => false,
			'allowAccountCreation' => !$parentBlock->gb_create_account,
			// Like local autoblocks, global autoblocks should not prevent email access
			'blockEmail' => false,
			// Global autoblocks should not trigger new autoblocks
			'enableAutoblock' => false,
			'parentBlockId' => $parentBlock->gb_id,
		] );
	}

	/**
	 * Get the expiry timestamp for an global autoblock created at the given time.
	 *
	 * If the parent block expiry is specified, the return value will be earlier
	 * than or equal to the parent block expiry.
	 *
	 * Modified copy of {@link DatabaseBlockStore::getAutoblockExpiry}.
	 *
	 * @param string $timestamp
	 * @param string|null $parentExpiry
	 * @return string
	 */
	private function getAutoblockExpiry( string $timestamp, ?string $parentExpiry = null ): string {
		$maxDuration = $this->options->get( 'GlobalBlockingAutoblockExpiry' );
		$expiry = wfTimestamp( TS_MW, (int)wfTimestamp( TS_UNIX, $timestamp ) + $maxDuration );
		if ( $parentExpiry !== null && $parentExpiry !== 'infinity' ) {
			$expiry = min( $parentExpiry, $expiry );
		}
		return $expiry;
	}

	/**
	 * Gets the target for the log entry for either a global unblock or global block.
	 *
	 * @param string $target The target provided by the user for the global block or unblock
	 * @return LinkTarget The target to be used for the log entry
	 */
	private function getTargetForLogEntry( string $target ): LinkTarget {
		// We need to use TitleValue::tryNew for block IDs, as the block ID contains a "#" character which
		// causes the title to be rejected by Title::makeTitleSafe. In all other cases we can use Title::makeTitleSafe.
		if ( GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			return TitleValue::tryNew( NS_USER, $target );
		} else {
			return Title::makeTitleSafe( NS_USER, $target );
		}
	}

	/**
	 * Remove a global block from a given IP address or range.
	 *
	 * @param string $target The target of the block to be removed, which can be an IP address, IP range, username, or
	 *   block ID which is prefixed with "#".
	 * @param string $reason The reason for removing the block which will be shown publicly in a log entry
	 *   for the unblock.
	 * @param UserIdentity $performer The user who is performing the unblock. The caller of this method is
	 *   responsible for determining if the performer has the necessary rights to perform the unblock.
	 * @return StatusValue An empty or fatal status
	 */
	public function unblock( string $target, string $reason, UserIdentity $performer ): StatusValue {
		$status = $this->validateGlobalBlockTarget( $target, $performer );

		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $status->getValue();

		$id = $this->globalBlockLookup->getGlobalBlockId( $data['targetForLookup'], DB_PRIMARY );
		if ( $id === 0 ) {
			return StatusValue::newFatal( 'globalblocking-notblocked', $data['targetForDisplay'] );
		}

		$dbw = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'globalblocks' )
			->where( $dbw->expr( 'gb_id', '=', $id )->or( 'gb_autoblock_parent_id', '=', $id ) )
			->caller( __METHOD__ )
			->execute();

		$logEntry = new ManualLogEntry( 'gblblock', 'gunblock' );
		$logEntry->setPerformer( $performer );
		$logEntry->setTarget( $this->getTargetForLogEntry( $data['targetForDisplay'] ) );
		$logEntry->setComment( $reason );
		$logEntry->setRelations( [ 'gb_id' => $id ] );
		$logId = $logEntry->insert();
		$logEntry->publish( $logId );

		return StatusValue::newGood();
	}

	/**
	 * Validates that:
	 * * If the target is an IP address range, it does not exceed range limits.
	 * * If the target is a user, the username has a valid central ID.
	 * * If the target is a block ID, the block ID is for an existing global block.
	 *
	 * @param string $target An IP address, IP range, a username, or global block ID
	 * @param UserIdentity $performer The performer of the action, used to appropriately hide the target if
	 *   necessary.
	 * @return GlobalBlockStatus Fatal if the target is not valid, along with a message to use for displaying to
	 *   the user. A good status otherwise, where the value of the status contains information about the
	 *   valid target with keys 'target', 'targetForDisplay', 'targetForLookup', 'targetCentralId',
	 *   'rangeStart', and 'rangeEnd'.
	 */
	public function validateGlobalBlockTarget( string $target, UserIdentity $performer ): GlobalBlockStatus {
		$targetForDisplay = $target;
		$targetForLookup = null;

		if ( GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			// If the $target is prefixed with "#" followed by digits, then this is a global block ID. Validate that
			// this ID corresponds to an active global block, returning a fatal if not.
			$targetForBlockId = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase()
				->newSelectQueryBuilder()
				->select( 'gb_address' )
				->from( 'globalblocks' )
				->where( [ 'gb_id' => substr( $target, 1 ) ] )
				->caller( __METHOD__ )
				->fetchField();

			if ( !$targetForBlockId ) {
				return GlobalBlockStatus::newFatal( 'globalblocking-notblocked-id', $target );
			}

			$targetForLookup = $target;
			$target = $targetForBlockId;
		}

		if ( !IPUtils::isIPAddress( $target ) ) {
			$centralIdForTarget = $this->centralIdLookup->centralIdFromName(
				$target,
				$this->userFactory->newFromUserIdentity( $performer )
			);
			if ( $centralIdForTarget === 0 ) {
				return GlobalBlockStatus::newFatal( 'globalblocking-block-target-invalid', $targetForDisplay );
			}
			return GlobalBlockStatus::newGood( [
				'target' => $target,
				'targetForDisplay' => $targetForDisplay,
				'targetForLookup' => $targetForLookup ?? $target,
				'targetCentralId' => $centralIdForTarget,
				// 'rangeStart' and 'rangeEnd' have to be strings and not null
				// due to the type of the DB columns.
				'rangeStart' => '',
				'rangeEnd' => '',
			] );
		}

		// Begin validation only performed if the target is an IP address or range.
		$target = IPUtils::sanitizeIP( $target );

		// Validate that the IP address is not a range that is too large.
		if ( IPUtils::isValidRange( $target ) ) {
			[ $prefix, $range ] = explode( '/', $target, 2 );
			$limit = $this->options->get( 'GlobalBlockingCIDRLimit' );
			$ipVersion = IPUtils::isIPv4( $prefix ) ? 'IPv4' : 'IPv6';
			if ( (int)$range < $limit[ $ipVersion ] ) {
				return GlobalBlockStatus::newFatal(
					'globalblocking-bigrange', $targetForDisplay, $ipVersion, $limit[ $ipVersion ]
				);
			}
		}

		// The IP address target is valid, so return the sanitized target along with
		// the start and the end of the range in hexadecimal (for a single IP address
		// this is hexadecimal representation of the single IP address).
		$data = [
			'targetCentralId' => 0, 'targetForDisplay' => $targetForDisplay,
		];

		[ $data[ 'rangeStart' ], $data[ 'rangeEnd' ] ] = IPUtils::parseRange( $target );

		if ( $data[ 'rangeStart' ] !== $data[ 'rangeEnd' ] ) {
			$data[ 'target' ] = IPUtils::sanitizeRange( $target );
		} else {
			$data[ 'target' ] = $target;
		}

		$data['targetForLookup'] = $targetForLookup ?? $data[ 'target' ];

		return GlobalBlockStatus::newGood( $data );
	}
}
