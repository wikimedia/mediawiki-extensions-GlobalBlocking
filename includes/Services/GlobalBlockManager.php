<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use ManualLogEntry;
use MediaWiki\Block\BlockUser;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Title\Title;
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
		'GlobalBlockingAllowGlobalAccountBlocks',
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

		if ( $anonOnly && !IPUtils::isIPAddress( $target ) ) {
			// Anon-only blocks on an account does not make any sense, so reject them.
			return StatusValue::newFatal( 'globalblocking-block-anononly-on-account', $target );
		}

		// Check for an existing block in the primary database database
		$existingBlock = $this->globalBlockLookup->getGlobalBlockId( $data[ 'target' ], DB_PRIMARY );
		if ( !$modify && $existingBlock ) {
			return StatusValue::newFatal( 'globalblocking-block-alreadyblocked', $data[ 'target' ] );
		}

		// At this point, we have validated that a block can be inserted or updated.

		$dbw = $this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase();
		$row = [
			'gb_address' => $data['target'],
			'gb_target_central_id' => $data['centralId'],
			'gb_by' => $blocker->getName(),
			'gb_by_central_id' => $this->centralIdLookup->centralIdFromLocalUser( $blocker ),
			'gb_by_wiki' => WikiMap::getCurrentWikiId(),
			'gb_reason' => $reason,
			'gb_timestamp' => $dbw->timestamp( wfTimestampNow() ),
			'gb_anon_only' => $anonOnly,
			'gb_expiry' => $dbw->encodeExpiry( $expiry ),
			'gb_range_start' => $data['rangeStart'],
			'gb_range_end' => $data['rangeEnd'],
		];

		$blockId = 0;
		if ( $modify && $existingBlock ) {
			$dbw->newUpdateQueryBuilder()
				->update( 'globalblocks' )
				->set( $row )
				->where( [ 'gb_id' => $existingBlock ] )
				->caller( __METHOD__ )
				->execute();
			if ( $dbw->affectedRows() ) {
				$blockId = $existingBlock;
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
			return StatusValue::newFatal( 'globalblocking-block-failure', $data[ 'target' ] );
		}

		return StatusValue::newGood( [
			'id' => $blockId,
		] );
	}

	/**
	 * Block an IP address or range.
	 *
	 * @param string $target The IP address, IP range, or username to block
	 * @param string $reason The public reason to be shown in the global block log,
	 *   on the global block list, and potentially to the blocked user when they try to edit.
	 * @param string $expiry Any expiry that can be parsed by BlockUser::parseExpiryInput, including infinite.
	 * @param UserIdentity $blocker The user performing the block. The caller of this method is
	 *    responsible for determining if the performer has the necessary rights to perform the block.
	 * @param array $options An array of options provided as values with numeric keys. This accepts:
	 *   - 'anon-only': If set, only anonymous users will be affected by the block
	 *   - 'modify': If set, the block will be modified if it already exists. If not set,
	 *       the block will fail if it already exists.
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

		// Log it.
		$logAction = $modify ? 'modify' : 'gblock';

		$logEntry = new ManualLogEntry( 'gblblock', $logAction );
		$logEntry->setPerformer( $blocker );
		$logEntry->setTarget( Title::makeTitleSafe( NS_USER, $target ) );
		$logEntry->setComment( $reason );

		$flags = [];
		if ( $anonOnly ) {
			$flags[] = 'anon-only';
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
	 * Remove a global block from a given IP address or range.
	 *
	 * @param string $target The target of the block to be removed, which can be an IP address, IP range, or username.
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
			if ( $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
				$errorMessageKey = 'globalblocking-notblocked-new';
			} else {
				$errorMessageKey = 'globalblocking-notblocked';
			}
			return StatusValue::newFatal( $errorMessageKey, $data['target'] );
		}

		$this->globalBlockingConnectionProvider->getPrimaryGlobalBlockingDatabase()
			->newDeleteQueryBuilder()
			->deleteFrom( 'globalblocks' )
			->where( [ 'gb_id' => $id ] )
			->caller( __METHOD__ )
			->execute();

		$logEntry = new ManualLogEntry( 'gblblock', 'gunblock' );
		$logEntry->setPerformer( $performer );
		$logEntry->setTarget( Title::makeTitleSafe( NS_USER, $data['target'] ) );
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
	 *
	 * @param string $target An IP address, IP range, or a username
	 * @return StatusValue Fatal if errors, Good if no errors
	 */
	private function validateInput( string $target, UserIdentity $performer ): StatusValue {
		if ( !IPUtils::isIPAddress( $target ) ) {
			if ( $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
				$centralIdForTarget = $this->centralIdLookup->centralIdFromName(
					$target,
					$this->userFactory->newFromUserIdentity( $performer )
				);
				if ( $centralIdForTarget === 0 ) {
					return StatusValue::newFatal( 'globalblocking-block-target-invalid', $target );
				}
				return StatusValue::newGood( [
					'target' => $target,
					'centralId' => $centralIdForTarget,
					// 'rangeStart' and 'rangeEnd' have to be strings and not null
					// due to the type of the DB columns.
					'rangeStart' => '',
					'rangeEnd' => '',
				] );
			} else {
				return StatusValue::newFatal( 'globalblocking-block-ipinvalid', $target );
			}
		}

		// Begin validation only performed if the target is an IP address or range.
		$target = IPUtils::sanitizeIP( $target );

		// Validate that the IP address is not a range that is too large.
		if ( IPUtils::isValidRange( $target ) ) {
			[ $prefix, $range ] = explode( '/', $target, 2 );
			$limit = $this->options->get( 'GlobalBlockingCIDRLimit' );
			$ipVersion = IPUtils::isIPv4( $prefix ) ? 'IPv4' : 'IPv6';
			if ( (int)$range < $limit[ $ipVersion ] ) {
				return StatusValue::newFatal( 'globalblocking-bigrange', $target, $ipVersion, $limit[ $ipVersion ] );
			}
		}

		// The IP address target is valid, so return the sanitized target along with
		// the start and the end of the range in hexadecimal (for a single IP address
		// this is hexadecimal representation of the single IP address).
		$data = [ 'centralId' => 0 ];

		[ $data[ 'rangeStart' ], $data[ 'rangeEnd' ] ] = IPUtils::parseRange( $target );

		if ( $data[ 'rangeStart' ] !== $data[ 'rangeEnd' ] ) {
			$data[ 'target' ] = IPUtils::sanitizeRange( $target );
		} else {
			$data[ 'target' ] = $target;
		}

		return StatusValue::newGood( $data );
	}
}
