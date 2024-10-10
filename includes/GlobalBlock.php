<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\WikiMap\WikiMap;
use stdClass;

class GlobalBlock extends AbstractBlock {
	private int $id;
	private bool $xff;
	private bool $isAutoBlock;
	private bool $isAutoblocking;
	private ?UserIdentity $blocker;

	/**
	 * Constructs a GlobalBlock instance from a database returned row returned by GlobalBlockLookup.
	 *
	 * @param stdClass $row The database row
	 * @param bool $xff Whether this was triggered by the XFF header containing a globally blocked IP address
	 * @return GlobalBlock
	 * @internal You should get a GlobalBlock instance through the {@link GlobalBlockLookup} service in most cases
	 */
	public static function newFromRow( stdClass $row, bool $xff ): GlobalBlock {
		return new GlobalBlock(
			[
				'id' => $row->gb_id,
				'isAutoblock' => boolval( $row->gb_autoblock_parent_id ),
				'enableAutoblock' => $row->gb_enable_autoblock,
				'byCentralId' => $row->gb_by_central_id,
				'byWiki' => $row->gb_by_wiki,
				'address' => $row->gb_address,
				'reason' => $row->gb_reason,
				'timestamp' => $row->gb_timestamp,
				'anonOnly' => $row->gb_anon_only,
				'expiry' => $row->gb_expiry,
				'createAccount' => $row->gb_create_account,
				'xff' => $xff,
			]
		);
	}

	/**
	 * @param array $options Parameters of the block, with options supported by {@link AbstractBlock::__construct} and:
	 *   - id: (int) The ID of the global block from the 'globalblocks' table
	 *   - isAutoblock: (bool) Is this an automatic block?
	 *   - enableAutoblock: (bool) Enable automatic blocking
	 *   - expiry: (string) Database timestamp of expiration of the block or 'infinity'
	 *   - anonOnly: (bool) Only disallow anonymous actions
	 *   - createAccount: (bool) Disallow creation of new accounts
	 *   - byCentralId: (int) Central ID of the blocker
	 *   - byWiki: (string) Wiki ID of the wiki where the blocker performed the block
	 *   - xff: (bool) Did this block match the current user because one of their XFF IPs were blocked?
	 *
	 * @internal You should get a GlobalBlock instance through the {@link GlobalBlockLookup} service.
	 */
	public function __construct( array $options ) {
		// We need to set isAutoBlock before calling the parent, because the parent calls ::getType which then
		// accesses this property.
		$this->isAutoBlock = (bool)$options['isAutoblock'];

		parent::__construct( $options );

		$db = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase( $this->getWikiId() );
		$this->setExpiry( $db->decodeExpiry( $options['expiry'] ) );

		$this->id = (int)$options['id'];
		$this->xff = (bool)$options['xff'];
		$this->isAutoblocking = (bool)$options['enableAutoblock'];
		$this->isCreateAccountBlocked( (bool)$options['createAccount'] );
		$this->setGlobalBlocker( $options );
	}

	/**
	 * @inheritDoc
	 */
	public function getBy( $wikiId = self::LOCAL ): int {
		$this->assertWiki( $wikiId );
		return ( $this->blocker ) ? $this->blocker->getId( $wikiId ) : 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getByName(): string {
		return ( $this->blocker ) ? $this->blocker->getName() : '';
	}

	/**
	 * @inheritDoc
	 */
	public function getBlocker(): ?UserIdentity {
		return $this->blocker;
	}

	/**
	 * @inheritDoc
	 */
	public function getIdentifier( $wikiId = self::LOCAL ) {
		return $this->getId( $wikiId );
	}

	/**
	 * @inheritDoc
	 * @return int
	 */
	public function getId( $wikiId = self::LOCAL ): int {
		return $this->id;
	}

	public function getXff() {
		return $this->xff;
	}

	/**
	 * Does the block cause autoblocks to be created?
	 *
	 * @return bool
	 */
	public function isAutoblocking(): bool {
		return $this->getType() == self::TYPE_USER ? $this->isAutoblocking : false;
	}

	/** @inheritDoc */
	public function getType(): ?int {
		return $this->isAutoBlock ? self::TYPE_AUTO : parent::getType();
	}

	/**
	 * @param array $options Options for the $block provided in ::__construct
	 */
	private function setGlobalBlocker( array $options ) {
		$services = MediaWikiServices::getInstance();
		$lookup = $services->getCentralIdLookup();

		$user = $lookup->localUserFromCentralId( $options['byCentralId'], CentralIdLookup::AUDIENCE_RAW );

		// If the block was inserted from this wiki, then we know the blocker exists
		if ( $user && $options['byWiki'] === WikiMap::getCurrentWikiId() ) {
			$this->blocker = $user;
			return;
		}

		// If the blocker is the same user on the foreign wiki and the current wiki
		// then we can use the username
		if ( $user && $user->getId() && $lookup->isAttached( $user )
			&& $lookup->isAttached( $user, $options['byWiki'] )
		) {
			$this->blocker = $user;
			return;
		}

		// They don't exist locally, so we need to use an interwiki username
		$username = $lookup->nameFromCentralId( $options['byCentralId'], CentralIdLookup::AUDIENCE_RAW );

		if ( $username !== null ) {
			$this->blocker = $services->getUserFactory()->newFromUserIdentity( UserIdentityValue::newExternal(
				$options['byWiki'], $username
			) );
			return;
		}

		// If all else fails, then set the blocker as null. This shouldn't happen unless the central ID for the
		// performer of the block is broken.
		$this->blocker = null;
	}
}
