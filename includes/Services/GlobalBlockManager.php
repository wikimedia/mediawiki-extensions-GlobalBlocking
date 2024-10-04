<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use ManualLogEntry;
use MediaWiki\Block\BlockUser;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use StatusValue;
use Wikimedia\IPUtils;

/**
 * A service for creating, updating, and removing global blocks.
 *
 * @since 1.42
 */
class GlobalBlockManager {

	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockingCIDRLimit',
	];

	private ServiceOptions $options;
	private GlobalBlockingBlockPurger $globalBlockingBlockPurger;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private CentralIdLookup $centralIdLookup;
	private UserFactory $userFactory;

	public function __construct(
		ServiceOptions $options,
		GlobalBlockingBlockPurger $globalBlockingBlockPurger,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		CentralIdLookup $centralIdLookup,
		UserFactory $userFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->globalBlockingBlockPurger = $globalBlockingBlockPurger;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->centralIdLookup = $centralIdLookup;
		$this->userFactory = $userFactory;
	}

	/**
	 * @param string $target See ::block for details.
	 * @param string $reason See ::block for details.
	 * @param string|false $expiry See ::block for details.
	 * @param UserIdentity $blocker See ::block for details.
	 * @param array $options See ::block for details.
	 * @return StatusValue
	 * @internal Use ::block instead. This is public to allow the deprecated static method in
	 *   GlobalBlocking to call this method. This will be made private once the deprecated method
	 *   is removed.
	 */
	public function insertBlock(
		string $target, string $reason, $expiry, UserIdentity $blocker, array $options = []
	): StatusValue {
		// As we are inserting a block and therefore will be using a primary DB connection,
		// we can purge expired blocks from the primary DB.
		$this->globalBlockingBlockPurger->purgeExpiredBlocks();

		if ( $expiry === false ) {
			return StatusValue::newFatal( 'globalblocking-block-expiryinvalid' );
		}

		$status = $this->validateInput( $target, $blocker );

		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $status->getValue();

		$modify = in_array( 'modify', $options );
		$anonOnly = in_array( 'anon-only', $options );
		$allowAccountCreation = in_array( 'allow-account-creation', $options );
		$enableAutoblock = in_array( 'enable-autoblock', $options );

		if ( $anonOnly && !IPUtils::isIPAddress( $data['target'] ) ) {
			// Anon-only blocks on an account does not make any sense, so reject them.
			return StatusValue::newFatal( 'globalblocking-block-anononly-on-account', $data['targetForDisplay'] );
		}

		if ( $enableAutoblock && !$data['targetCentralId'] ) {
			// Global blocks can only be autoblocking if they target a user.
			return StatusValue::newFatal( 'globalblocking-block-enable-autoblock-on-ip', $data['targetForDisplay'] );
		}

		// Check for an existing block in the primary database database
		$existingBlock = $this->globalBlockLookup->getGlobalBlockId( $data[ 'target' ], DB_PRIMARY );
		if ( !$modify && $existingBlock ) {
			return StatusValue::newFatal( 'globalblocking-block-alreadyblocked', $data['targetForDisplay'] );
		}

		// At this point, we have validated that a block can be inserted or updated.
		return $this->insertBlockAfterChecks( array_merge( [
			'byCentralId' => $this->centralIdLookup->centralIdFromLocalUser( $blocker ),
			'byWiki' => WikiMap::getCurrentWikiId(),
			'reason' => $reason,
			'timestamp' => wfTimestampNow(),
			'expiry' => $expiry,
			'anonOnly' => $anonOnly,
			'allowAccountCreation' => $allowAccountCreation,
			'enableAutoblock' => $enableAutoblock,
			'existingBlockId' => $existingBlock,
		], $data ) );
	}

	/**
	 * Insert or update a global block with the properties specified in $data.
	 *
	 * @param array $data Attributes to be set for the block.
	 *    Required: target (string), targetForDisplay (string), targetCentralId (int), rangeStart (string),
	 *       rangeEnd (string), byCentralId (int), byWiki (string), reason (string), timestamp (any valid timestamp),
	 *       expiry (any valid timestamp or infinity), anonOnly (bool), allowAccountCreation (bool),
	 *       enableAutoblock (bool)
	 *    Optional: parentBlockId (int, default 0), existingBlockId (int, default 0)
	 * @return StatusValue
	 */
	private function insertBlockAfterChecks( array $data ): StatusValue {
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
			return StatusValue::newFatal( 'globalblocking-block-failure', $data['targetForDisplay'] );
		}

		return StatusValue::newGood( [
			'id' => $blockId,
		] );
	}

	/**
	 * Block an IP address or range.
	 *
	 * @param string $target The IP address, IP range, username, or block ID which is prefixed with "#".
	 * @param string $reason The public reason to be shown in the global block log,
	 *   on the global block list, and potentially to the blocked user when they try to edit.
	 * @param string $expiry Any expiry that can be parsed by BlockUser::parseExpiryInput, including infinite.
	 * @param UserIdentity $blocker The user performing the block. The caller of this method is
	 *    responsible for determining if the performer has the necessary rights to perform the block.
	 * @param array $options An array of options provided as values with numeric keys. This accepts:
	 *   - 'anon-only': If set, only anonymous users will be affected by the block
	 *   - 'modify': If set, the block will be modified if it already exists. If not set,
	 *       the block will fail if it already exists.
	 *   - 'allow-account-creation': If set, the block will allow account creation. Otherwise,
	 *       the block will prevent account creation.
	 *   - 'enable-autoblock': If set, the block will cause autoblocks.
	 * @return StatusValue A status object, with errors if the block failed.
	 */
	public function block(
		string $target, string $reason, string $expiry, UserIdentity $blocker, array $options = []
	): StatusValue {
		$expiry = BlockUser::parseExpiryInput( $expiry );
		$status = $this->insertBlock( $target, $reason, $expiry, $blocker, $options );

		if ( !$status->isOK() ) {
			return $status;
		}

		$blockId = $status->getValue()['id'];
		$anonOnly = in_array( 'anon-only', $options );
		$modify = in_array( 'modify', $options );
		$allowAccountCreation = in_array( 'allow-account-creation', $options );
		$enableAutoblock = in_array( 'enable-autoblock', $options );

		// Log it.
		$logAction = $modify ? 'modify' : 'gblock';

		$logEntry = new ManualLogEntry( 'gblblock', $logAction );
		$logEntry->setPerformer( $blocker );
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

		// The 4th parameter is the target as plaintext used for GENDER support and is added by the log formatter.
		$logEntry->setParameters( [
			'5::expiry' => $expiry,
			// List of flags which are then converted to a comma separated localised list by the log formatter
			'6::flags' => $flags,
		] );
		$logEntry->setRelations( [ 'gb_id' => $blockId ] );
		$logId = $logEntry->insert();
		$logEntry->publish( $logId );

		return $status;
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
		$status = $this->validateInput( $target, $performer );

		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $status->getValue();

		$id = $this->globalBlockLookup->getGlobalBlockId( $data[ 'target' ], DB_PRIMARY );
		if ( $id === 0 ) {
			return StatusValue::newFatal( 'globalblocking-notblocked', $data['targetForDisplay'] );
		}

		$this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase()
			->newDeleteQueryBuilder()
			->deleteFrom( 'globalblocks' )
			->where( [ 'gb_id' => $id ] )
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
	 * @return StatusValue Fatal if errors, Good if no errors
	 */
	private function validateInput( string $target, UserIdentity $performer ): StatusValue {
		$targetForDisplay = $target;

		if ( GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			// If the $target is prefixed with "#" followed by digits, then this is a global block ID. Validate that
			// this ID corresponds to an active global block, returning a fatal if not.
			$targetForBlockId = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase()
				->newSelectQueryBuilder()
				->select( 'gb_address' )
				->from( 'globalblocks' )
				->where( [ 'gb_id' => substr( $target, 1 ) ] )
				->fetchField();

			if ( !$targetForBlockId ) {
				return StatusValue::newFatal( 'globalblocking-notblocked-id', $target );
			}

			$target = $targetForBlockId;
		}

		if ( !IPUtils::isIPAddress( $target ) ) {
			$centralIdForTarget = $this->centralIdLookup->centralIdFromName(
				$target,
				$this->userFactory->newFromUserIdentity( $performer )
			);
			if ( $centralIdForTarget === 0 ) {
				return StatusValue::newFatal( 'globalblocking-block-target-invalid', $targetForDisplay );
			}
			return StatusValue::newGood( [
				'target' => $target,
				'targetForDisplay' => $targetForDisplay,
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
				return StatusValue::newFatal(
					'globalblocking-bigrange', $targetForDisplay, $ipVersion, $limit[ $ipVersion ]
				);
			}
		}

		// The IP address target is valid, so return the sanitized target along with
		// the start and the end of the range in hexadecimal (for a single IP address
		// this is hexadecimal representation of the single IP address).
		$data = [ 'targetCentralId' => 0, 'targetForDisplay' => $targetForDisplay ];

		[ $data[ 'rangeStart' ], $data[ 'rangeEnd' ] ] = IPUtils::parseRange( $target );

		if ( $data[ 'rangeStart' ] !== $data[ 'rangeEnd' ] ) {
			$data[ 'target' ] = IPUtils::sanitizeRange( $target );
		} else {
			$data[ 'target' ] = $target;
		}

		return StatusValue::newGood( $data );
	}
}
