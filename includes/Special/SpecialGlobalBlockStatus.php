<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use ErrorPageError;
use Exception;
use HTMLForm;
use MediaWiki\Block\BlockUtils;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNameUtils;
use Wikimedia\IPUtils;

class SpecialGlobalBlockStatus extends FormSpecialPage {
	private ?string $mTarget;
	private ?bool $mCurrentStatus;
	private ?bool $mWhitelistStatus;

	private BlockUtils $blockUtils;
	private UserNameUtils $userNameUtils;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockLocalStatusManager $globalBlockLocalStatusManager;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	/**
	 * @param BlockUtils $blockUtils
	 * @param UserNameUtils $userNameUtils
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockLocalStatusManager $globalBlockLocalStatusManager
	 * @param GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 */
	public function __construct(
		BlockUtils $blockUtils,
		UserNameUtils $userNameUtils,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockLocalStatusManager $globalBlockLocalStatusManager,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		parent::__construct( 'GlobalBlockStatus', 'globalblock-whitelist' );
		$this->blockUtils = $blockUtils;
		$this->userNameUtils = $userNameUtils;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockLocalStatusManager = $globalBlockLocalStatusManager;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
	}

	/** @inheritDoc */
	public function execute( $par ) {
		$this->addHelpLink( 'Extension:GlobalBlocking' );
		$out = $this->getOutput();
		$out->disableClientCache();
		$out->setSubtitle( $this->globalBlockingLinkBuilder->buildSubtitleLinks( $this ) );

		parent::execute( $par );
	}

	/**
	 * @param string|null $par Parameter from the URL, may be null or a string (probably an IP)
	 * that was inserted
	 * @return void
	 * @throws Exception
	 */
	protected function setParameter( $par ) {
		$request = $this->getRequest();

		// Parse the target from the request or URL.
		$target = trim( $request->getText( 'address', $par ?? '' ) );
		if ( !$target ) {
			// If no target was provided, show the form with an empty target and the disable checkbox checked (as this
			// is the most common action).
			$this->mTarget = $target;
			$this->mCurrentStatus = true;
			$this->mWhitelistStatus = false;
			return;
		}

		// If the target is an IP address, sanitize it before assigning it as the target.
		if ( IPUtils::isValidRange( $target ) ) {
			$this->mTarget = IPUtils::sanitizeRange( $target );
		} elseif ( IPUtils::isIPAddress( $target ) ) {
			$this->mTarget = IPUtils::sanitizeIP( $target );
		} else {
			$normalisedTarget = $this->userNameUtils->getCanonical( $target );
			if ( $normalisedTarget ) {
				$this->mTarget = $normalisedTarget;
			} else {
				// Allow invalid targets to be set, so that the user can be shown an error message.
				$this->mTarget = $target;
			}
		}

		// Set the relevant user for the skin and assign it before the form is rendered to HTML.
		[ $targetForSkin ] = $this->blockUtils->parseBlockTarget( $target );
		if ( $targetForSkin instanceof UserIdentity ) {
			$this->getSkin()->setRelevantUser( $targetForSkin );
		}

		$this->mCurrentStatus = (bool)$this->globalBlockLocalStatusLookup
			->getLocalWhitelistInfo( $this->globalBlockLookup->getGlobalBlockId( $this->mTarget ) );
		$this->mWhitelistStatus = $request->getCheck( 'wpWhitelistStatus' );
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setPreHtml( $this->msg( 'globalblocking-whitelist-intro' )->parse() );
		$form->setWrapperLegendMsg( 'globalblocking-whitelist-legend' );
		$form->setSubmitTextMsg( 'globalblocking-whitelist-submit' );
	}

	protected function getFormFields() {
		$accountBlocksEnabled = $this->getConfig()->get( 'GlobalBlockingAllowGlobalAccountBlocks' );
		return [
			'address' => [
				'name' => 'address',
				'type' => 'text',
				'id' => 'mw-globalblocking-ipaddress mw-globalblocking-target',
				'label-message' => $accountBlocksEnabled ? 'globalblocking-target' : 'globalblocking-ipaddress',
				'default' => $this->mTarget,
				'required' => true,
			],
			'Reason' => [
				'type' => 'text',
				'label-message' => 'globalblocking-whitelist-reason'
			],
			'WhitelistStatus' => [
				'type' => 'check',
				'label-message' => 'globalblocking-whitelist-statuslabel',
				'default' => $this->mCurrentStatus
			]
		];
	}

	public function onSubmit( array $data ) {
		if ( $this->mWhitelistStatus ) {
			// Locally disable the block
			$status = $this->globalBlockLocalStatusManager
				->locallyDisableBlock( $this->mTarget, $data['Reason'], $this->getUser() );
			$successMsg = 'globalblocking-whitelist-whitelisted';
		} else {
			// Locally re-enable the block
			$status = $this->globalBlockLocalStatusManager
				->locallyEnableBlock( $this->mTarget, $data['Reason'], $this->getUser() );
			$successMsg = 'globalblocking-whitelist-dewhitelisted';
		}

		if ( !$status->isGood() ) {
			return $status;
		}

		return $this->showSuccess( $this->mTarget, $status->getValue()['id'], $successMsg );
	}

	/**
	 * Show a message indicating that the change in the local status of the global block was successful.
	 *
	 * @param string $target The target of the global block that had its local status modified
	 * @param int $id The ID of the global block that had its local status modified (same as the ID in gbw_id).
	 * @param string $successMsg The message key used as the success message.
	 * @return true
	 */
	protected function showSuccess( string $target, int $id, string $successMsg ): bool {
		$out = $this->getOutput();
		$out->addWikiMsg( $successMsg, $target, $id );
		$out->addHTML( $this->getLinkRenderer()->makeKnownLink(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$this->msg( 'globalblocking-return' )->text()
		) );
		return true;
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

	public function getDescription() {
		return $this->msg( 'globalblocking-whitelist' );
	}

	protected function checkExecutePermissions( User $user ) {
		parent::checkExecutePermissions( $user );
		// If wgApplyGlobalBlocks is false, the user should not be able to access this special page.
		if ( !$this->getConfig()->get( 'ApplyGlobalBlocks' ) ) {
			throw new ErrorPageError( $this->getDescription(), 'globalblocking-whitelist-notapplied' );
		}
	}
}
