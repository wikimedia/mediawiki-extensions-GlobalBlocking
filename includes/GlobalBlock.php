<?php

class GlobalBlock extends Block {
	/**
	 * @var array
	 */
	protected $error;

	/**
	 * @param stdClass $block
	 * @param array $error
	 */
	public function __construct( stdClass $block, array $error ) {
		parent::__construct();

		$this->error = $error;
		$this->setGlobalBlocker( $block );
	}

	/**
	 * @inheritDoc
	 */
	public function getPermissionsError( IContextSource $context ) {
		return $this->error;
	}

	/**
	 * Block requires that the blocker exist or be an interwiki username, so do
	 * some validation to figure out what we need to use (T182344)
	 *
	 * @param stdClass $block DB row from globalblocks table
	 */
	public function setGlobalBlocker( stdClass $block ) {
		// If the block was inserted from this wiki, then we know the blocker exists
		if ( $block->gb_by_wiki === wfWikiID() ) {
			$this->setBlocker( $block->gb_by );
			return;
		}
		$user = User::newFromName( $block->gb_by );
		// If the blocker is the same user on the foreign wiki and the current wiki
		// then we can use the username
		$lookup = CentralIdLookup::factory();
		if ( $user->getId() && $lookup->isAttached( $user )
			&& $lookup->isAttached( $user, $block->gb_by_wiki )
		) {
			$this->setBlocker( $user );
			return;
		}

		// They don't exist locally, so we need to use an interwiki username
		$this->setBlocker( "{$block->gb_by_wiki}>{$block->gb_by}" );
	}
}
