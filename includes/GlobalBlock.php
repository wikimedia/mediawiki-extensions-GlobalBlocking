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
		$this->setBlocker( $block->gb_by );
	}

	/**
	 * @inheritDoc
	 */
	public function getPermissionsError( IContextSource $context ) {
		return $this->error;
	}
}
