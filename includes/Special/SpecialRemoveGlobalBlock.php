<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use MediaWiki\Block\BlockUtils;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Extension\GlobalBlocking\Widget\HTMLUserTextFieldAllowingGlobalBlockIds;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNameUtils;
use Wikimedia\IPUtils;

class SpecialRemoveGlobalBlock extends FormSpecialPage {
	private string $target;

	private BlockUtils $blockUtils;
	private UserNameUtils $userNameUtils;
	private GlobalBlockManager $globalBlockManager;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	/**
	 * @param BlockUtils $blockUtils
	 * @param UserNameUtils $userNameUtils
	 * @param GlobalBlockManager $globalBlockManager
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 */
	public function __construct(
		BlockUtils $blockUtils,
		UserNameUtils $userNameUtils,
		GlobalBlockManager $globalBlockManager,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		parent::__construct( 'RemoveGlobalBlock', 'globalblock' );
		$this->blockUtils = $blockUtils;
		$this->userNameUtils = $userNameUtils;
		$this->globalBlockManager = $globalBlockManager;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
	}

	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );

		$out = $this->getOutput();
		$out->setPageTitleMsg( $this->msg( 'globalblocking-unblock' ) );
		$out->setSubtitle( $this->globalBlockingLinkBuilder->buildSubtitleLinks( $this ) );
		$out->disableClientCache();

		[ $target ] = $this->blockUtils->parseBlockTarget( $par );

		if ( $target instanceof UserIdentity ) {
			$this->getSkin()->setRelevantUser( $target );
		}
	}

	protected function setParameter( $par ) {
		parent::setParameter( $par );

		// 'address' is a old name for the 'target' field, which is retained for B/C
		$request = $this->getRequest();
		$target = trim( $request->getText( 'target', $request->getText( 'address', $par ?? '' ) ) );
		if ( IPUtils::isValidRange( $target ) ) {
			$target = IPUtils::sanitizeRange( $target );
		} elseif ( IPUtils::isValid( $target ) ) {
			$target = IPUtils::sanitizeIP( $target );
		} else {
			$target = $this->userNameUtils->getCanonical( $target ) ?: $target;
		}
		'@phan-var string $target';
		$this->target = $target;
	}

	/** @inheritDoc */
	public function onSubmit( array $data ) {
		return Status::wrap( $this->globalBlockManager->unblock( $this->target, $data['reason'], $this->getUser() ) );
	}

	public function onSuccess() {
		$successMsgKey = 'globalblocking-unblock-unblocked';
		$target = $this->target;

		// Display the global block ID specific message if the target is a global block ID.
		$globalBlockId = GlobalBlockLookup::isAGlobalBlockId( $this->target );
		if ( $globalBlockId ) {
			$successMsgKey .= '-for-id-target';
			// Use the ID without the "#" prefix, as this is added by the message.
			$target = $globalBlockId;
		}

		// Display the success message and also a link to go to Special:GlobalBlockList
		$msg = $this->msg( $successMsgKey, $target )->parseAsBlock();
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
			'target' => [
				'name' => 'target',
				'class' => HTMLUserTextFieldAllowingGlobalBlockIds::class,
				'ipallowed' => true,
				'iprange' => true,
				'id' => 'mw-globalblocking-target',
				'label-message' => 'globalblocking-target-with-block-ids',
				'required' => true,
				'default' => $this->target,
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
