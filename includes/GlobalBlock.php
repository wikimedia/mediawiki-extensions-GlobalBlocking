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
	private ?UserIdentity $blocker;

	/**
	 * @param stdClass $block
	 * @param array $options
	 */
	public function __construct( stdClass $block, $options ) {
		parent::__construct( $options );

		$db = MediaWikiServices::getInstance()->getConnectionProvider()
			->getReplicaDatabase( $this->getWikiId() );
		$this->setExpiry( $db->decodeExpiry( $options['expiry'] ) );

		$this->id = $block->gb_id;
		$this->xff = (bool)$options['xff'];
		$this->setGlobalBlocker( $block );
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

	/** @inheritDoc */
	public function getId( $wikiId = self::LOCAL ): ?int {
		return $this->id;
	}

	public function getXff() {
		return $this->xff;
	}

	/**
	 * @param stdClass $block DB row from globalblocks table
	 */
	public function setGlobalBlocker( stdClass $block ) {
		$services = MediaWikiServices::getInstance();
		$lookup = $services->getCentralIdLookup();

		$user = $lookup->localUserFromCentralId( $block->gb_by_central_id, CentralIdLookup::AUDIENCE_RAW );

		// If the block was inserted from this wiki, then we know the blocker exists
		if ( $user && $block->gb_by_wiki === WikiMap::getCurrentWikiId() ) {
			$this->blocker = $user;
			return;
		}

		// If the blocker is the same user on the foreign wiki and the current wiki
		// then we can use the username
		if ( $user && $user->getId() && $lookup->isAttached( $user )
			&& $lookup->isAttached( $user, $block->gb_by_wiki )
		) {
			$this->blocker = $user;
			return;
		}

		// They don't exist locally, so we need to use an interwiki username
		$username = $lookup->nameFromCentralId( $block->gb_by_central_id, CentralIdLookup::AUDIENCE_RAW );

		if ( $username !== null ) {
			$this->blocker = $services->getUserFactory()->newFromUserIdentity( UserIdentityValue::newExternal(
				$block->gb_by_wiki, $username
			) );
			return;
		}

		// If all else fails, then set the blocker as null. This shouldn't happen unless the central ID for the
		// performer of the block is broken.
		$this->blocker = null;
	}

	/**
	 * @inheritDoc
	 */
	public function isCreateAccountBlocked( $x = null ): bool {
		return true;
	}
}
