<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use Exception;
use HTMLForm;
use MediaWiki\Block\BlockUtils;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusManager;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\UserIdentity;
use Wikimedia\IPUtils;

class SpecialGlobalBlockStatus extends FormSpecialPage {
	private ?string $mAddress;
	private ?bool $mCurrentStatus;
	private ?bool $mWhitelistStatus;

	private BlockUtils $blockUtils;
	private GlobalBlockLocalStatusManager $globalBlockLocalStatusManager;

	/**
	 * @param BlockUtils $blockUtils
	 * @param GlobalBlockLocalStatusManager $globalBlockLocalStatusManager
	 */
	public function __construct(
		BlockUtils $blockUtils,
		GlobalBlockLocalStatusManager $globalBlockLocalStatusManager
	) {
		parent::__construct( 'GlobalBlockStatus', 'globalblock-whitelist' );
		$this->blockUtils = $blockUtils;
		$this->globalBlockLocalStatusManager = $globalBlockLocalStatusManager;
	}

	/**
	 * @param string $par Parameters of the URL, probably the IP being actioned
	 */
	public function execute( $par ) {
		$this->loadParameters( $par );
		$this->setHeaders();
		$this->addHelpLink( 'Extension:GlobalBlocking' );
		$this->checkExecutePermissions( $this->getUser() );

		$out = $this->getOutput();
		$out->disableClientCache();
		$out->setPageTitleMsg( $this->msg( 'globalblocking-whitelist' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );

		if ( !$this->getConfig()->get( 'ApplyGlobalBlocks' ) ) {
			$out->addWikiMsg( 'globalblocking-whitelist-notapplied' );
			return;
		}
		$this->getForm()->show();

		[ $target ] = $this->blockUtils->parseBlockTarget( $this->mAddress );

		if ( $target instanceof UserIdentity ) {
			$this->getSkin()->setRelevantUser( $target );
		}
	}

	/**
	 * @param string|null $par Parameter from the URL, may be null or a string (probably an IP)
	 * that was inserted
	 * @return void
	 * @throws Exception
	 */
	private function loadParameters( ?string $par ) {
		$request = $this->getRequest();
		$ip = trim( $request->getText( 'address', $par ?? '' ) );
		$this->mAddress = ( $ip !== '' || $request->wasPosted() ) ? IPUtils::sanitizeRange( $ip ) : '';
		$this->mWhitelistStatus = $request->getCheck( 'wpWhitelistStatus' );
		$id = GlobalBlocking::getGlobalBlockId( $this->mAddress );

		if ( $this->mAddress ) {
			$this->mCurrentStatus = ( GlobalBlocking::getLocalWhitelistInfo( $id, $this->mAddress ) !== false );
			if ( !$request->wasPosted() ) {
				$this->mWhitelistStatus = $this->mCurrentStatus;
			}
		} else {
			$this->mCurrentStatus = true;
		}
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setPreHtml( $this->msg( 'globalblocking-whitelist-intro' )->parse() );
		$form->setWrapperLegendMsg( 'globalblocking-whitelist-legend' );
		$form->setSubmitTextMsg( 'globalblocking-whitelist-submit' );
	}

	protected function getFormFields() {
		return [
			'address' => [
				'name' => 'address',
				'type' => 'text',
				'id' => 'mw-globalblocking-ipaddress',
				'label-message' => 'globalblocking-ipaddress',
				'default' => $this->mAddress,
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
				->locallyDisableBlock( $this->mAddress, $data['Reason'], $this->getUser() );
			$successMsg = 'globalblocking-whitelist-whitelisted';
		} else {
			// Locally re-enable the block
			$status = $this->globalBlockLocalStatusManager
				->locallyEnableBlock( $this->mAddress, $data['Reason'], $this->getUser() );
			$successMsg = 'globalblocking-whitelist-dewhitelisted';
		}

		if ( !$status->isGood() ) {
			return $status;
		}

		return $this->showSuccess( $this->mAddress, $status->getValue()['id'], $successMsg );
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
}
