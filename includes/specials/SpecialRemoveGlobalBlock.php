<?php

use Wikimedia\IPUtils;

class SpecialRemoveGlobalBlock extends FormSpecialPage {
	/** @var string|null */
	private $ip;

	public function __construct() {
		parent::__construct( 'RemoveGlobalBlock', 'globalblock' );
	}

	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'globalblocking-unblock' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->enableClientCache( false );
	}

	/**
	 * @param array $data
	 * @return array|bool
	 */
	public function onSubmit( array $data ) {
		$errors = GlobalBlocking::unblock( $data['ipaddress'], $data['reason'], $this->getUser() );

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		$this->ip = IPUtils::sanitizeIP( $data[ 'ipaddress' ] );

		[ $rangeStart, $rangeEnd ] = IPUtils::parseRange( $this->ip );

		if ( $rangeStart !== $rangeEnd ) {
			$this->ip = IPUtils::sanitizeRange( $this->ip );
		}

		return true;
	}

	public function onSuccess() {
		$msg = $this->msg( 'globalblocking-unblock-unblocked', $this->ip )->parseAsBlock();
		$link = $this->getLinkRenderer()->makeKnownLink(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$this->msg( 'globalblocking-return' )->text()
		);

		$this->getOutput()->addHTML( $msg . $link );
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setWrapperLegendMsg( 'globalblocking-unblock-legend' );
		$form->setSubmitTextMsg( 'globalblocking-unblock-submit' );
		$form->setPreHtml( $this->msg( 'globalblocking-unblock-intro' )->parse() );
	}

	protected function getFormFields() {
		return [
			'ipaddress' => [
				'name' => 'address',
				'type' => 'text',
				'id' => 'mw-globalblocking-ipaddress',
				'label-message' => 'globalblocking-ipaddress',
				'required' => true,
				'default' => $this->par,
			],
			'reason' => [
				'name' => 'wpReason',
				'type' => 'text',
				'id' => 'mw-globalblocking-unblock-reason',
				'label-message' => 'globalblocking-unblock-reason',
			],
		];
	}

	public function doesWrites() {
		return true;
	}

	protected function getGroupName() {
		return 'users';
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}
}
