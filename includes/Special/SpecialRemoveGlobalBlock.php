<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use HTMLForm;
use MediaWiki\Block\BlockUtils;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use Wikimedia\IPUtils;

class SpecialRemoveGlobalBlock extends FormSpecialPage {
	/** @var string|null */
	private $ip;

	/** @var BlockUtils */
	private $blockUtils;

	/**
	 * @param BlockUtils $blockUtils
	 */
	public function __construct(
		BlockUtils $blockUtils
	) {
		parent::__construct( 'RemoveGlobalBlock', 'globalblock' );
		$this->blockUtils = $blockUtils;
	}

	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );

		$out = $this->getOutput();
		$out->setPageTitleMsg( $this->msg( 'globalblocking-unblock' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->disableClientCache();

		[ $target ] = $this->blockUtils->parseBlockTarget( $par );

		if ( $target instanceof UserIdentity ) {
			$this->getSkin()->setRelevantUser( $target );
		}
	}

	/** @inheritDoc */
	public function onSubmit( array $data ) {
		$status = GlobalBlocking::unblock( $data['ipaddress'], $data['reason'], $this->getUser() );

		if ( !$status->isOK() ) {
			return Status::wrap( $status );
		}

		$this->ip = IPUtils::sanitizeIP( $data[ 'ipaddress' ] );

		[ $rangeStart, $rangeEnd ] = IPUtils::parseRange( $this->ip );

		if ( $rangeStart !== $rangeEnd ) {
			$this->ip = IPUtils::sanitizeRange( $this->ip );
		}

		return Status::newGood();
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
