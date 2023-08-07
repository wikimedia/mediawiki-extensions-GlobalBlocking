<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use stdClass;
use User;

class GlobalBlock extends AbstractBlock {
	/** @var int */
	private $id;

	/** @var array */
	protected $error;

	/** @var bool */
	protected $xff;

	/** @var UserIdentity|null */
	private $blocker;

	/**
	 * @param stdClass $block
	 * @param array $error
	 * @param array $options
	 */
	public function __construct( stdClass $block, array $error, $options ) {
		parent::__construct( $options );

		$db = MediaWikiServices::getInstance()
			->getDBLoadBalancerFactory()
			->getMainLB( $this->getWikiId() )
			->getConnection( DB_REPLICA, [], $this->getWikiId() );
		$this->setExpiry( $db->decodeExpiry( $options['expiry'] ) );

		$this->id = $block->gb_id;
		$this->error = $error;
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
		$user = User::newFromName( $block->gb_by );
		// If the block was inserted from this wiki, then we know the blocker exists
		if ( $block->gb_by_wiki === WikiMap::getCurrentWikiId() ) {
			$this->blocker = $user;
			return;
		}
		// If the blocker is the same user on the foreign wiki and the current wiki
		// then we can use the username
		$lookup = MediaWikiServices::getInstance()
			->getCentralIdLookupFactory()
			->getLookup();
		if ( $user->getId() && $lookup->isAttached( $user )
			&& $lookup->isAttached( $user, $block->gb_by_wiki )
		) {
			$this->blocker = $user;
			return;
		}

		// They don't exist locally, so we need to use an interwiki username
		$this->blocker = User::newFromName( "{$block->gb_by_wiki}>{$block->gb_by}", false );
	}

	/**
	 * @inheritDoc
	 */
	public function appliesToRight( $right ) {
		$res = parent::appliesToRight( $right );
		switch ( $right ) {
			case 'upload':
				return true;
			case 'createaccount':
				return true;
		}
		return $res;
	}

	/**
	 * @inheritDoc
	 */
	public function appliesToPasswordReset() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isCreateAccountBlocked( $x = null ): bool {
		return true;
	}
}
