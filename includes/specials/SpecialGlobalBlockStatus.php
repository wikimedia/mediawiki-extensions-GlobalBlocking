<?php

class SpecialGlobalBlockStatus extends FormSpecialPage {
	private $mAddress, $mCurrentStatus, $mWhitelistStatus;

	function __construct() {
		parent::__construct( 'GlobalBlockStatus', 'globalblock-whitelist' );
	}

	/**
	 * @param string $par not used currently
	 */
	function execute( $par ) {
		global $wgApplyGlobalBlocks;
		$this->setHeaders();
		$this->checkExecutePermissions( $this->getUser() );

		$out = $this->getOutput();
		$out->enableClientCache( false );
		$out->setPageTitle( $this->msg( 'globalblocking-whitelist' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );

		if ( !$wgApplyGlobalBlocks ) {
			$out->addWikiMsg( 'globalblocking-whitelist-notapplied' );
			return;
		}
		$this->loadParameters();
		$this->getForm()->show();
	}

	private function loadParameters() {
		$request = $this->getRequest();
		$ip = trim( $request->getText( 'address' ) );
		$this->mAddress = ( $ip !== '' || $request->wasPosted() ) ? IP::sanitizeRange( $ip ) : '';
		$this->mWhitelistStatus = $request->getCheck( 'wpWhitelistStatus' );
		$id = GlobalBlocking::getGlobalBlockId( $ip );

		if ( $this->mAddress ) {
			$this->mCurrentStatus = ( GlobalBlocking::getWhitelistInfo( $id, $this->mAddress ) !== false );
			if ( !$request->wasPosted() ) {
				$this->mWhitelistStatus = $this->mCurrentStatus;
			}
		} else {
			$this->mCurrentStatus = true;
		}
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setPreText( $this->msg( 'globalblocking-whitelist-intro' )->parse() );
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
		$ip = $this->mAddress;

		$id = GlobalBlocking::getGlobalBlockId( $ip );
		// Is it blocked?
		if ( !$id ) {
			return [ [ 'globalblocking-notblocked', $ip ] ];
		}

		// Local status wasn't changed.
		if ( $this->mCurrentStatus == $this->mWhitelistStatus ) {
			return [ 'globalblocking-whitelist-nochange' ];
		}

		$dbw = wfGetDB( DB_MASTER );
		GlobalBlocking::purgeExpired();

		if ( $this->mWhitelistStatus == true ) {
			// Add to whitelist

			// Find the expiry of the block. This is important so that we can store it in the
			// global_block_whitelist table, which allows us to purge it when the block has expired.
			$gdbr = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );
			$expiry = $gdbr->selectField( 'globalblocks', 'gb_expiry', [ 'gb_id' => $id ], __METHOD__ );

			$row = [
				'gbw_by' => $this->getUser()->getId(),
				'gbw_by_text' => $this->getUser()->getName(),
				'gbw_reason' => trim( $data['Reason'] ),
				'gbw_address' => $ip,
				'gbw_expiry' => $expiry,
				'gbw_id' => $id
			];
			if ( GlobalBlocking::getWhitelistInfoByIP( $this->mAddress ) !== false ) {
				// Check if there is already an entry with the same ip (and another id)
				$dbw->delete( 'global_block_whitelist', [ 'gbw_address' => $ip ], __METHOD__ );
				$dbw->replace( 'global_block_whitelist', [ 'gbw_id' ], $row, __METHOD__ );
			} else {
				$dbw->replace( 'global_block_whitelist', [ 'gbw_id' ], $row, __METHOD__ );
			}

			$this->addLogEntry( 'whitelist', $ip, $data['Reason'] );
			$successMsg = 'globalblocking-whitelist-whitelisted';
		} else {
			// Remove from whitelist
			$dbw->delete( 'global_block_whitelist', [ 'gbw_id' => $id ], __METHOD__ );

			$this->addLogEntry( 'dwhitelist', $ip, $data['Reason'] );
			$successMsg = 'globalblocking-whitelist-dewhitelisted';
		}

		return $this->showSuccess( $ip, $id, $successMsg );
	}

	/**
	 * @param string $action either 'whitelist' or 'dwhitelist'
	 * @param string $target Target IP
	 * @param string $reason
	 */
	protected function addLogEntry( $action, $target, $reason ) {
		$logEntry = new ManualLogEntry( 'gblblock', $action );
		$logEntry->setTarget( Title::makeTitleSafe( NS_USER, $target ) );
		$logEntry->setComment( $reason );
		$logEntry->setPerformer( $this->getUser() );
		$logId = $logEntry->insert();
		$logEntry->publish( $logId );
	}

	/**
	 * @param string $ip
	 * @param int $id
	 * @param string $successMsg
	 * @return bool
	 */
	protected function showSuccess( $ip, $id, $successMsg ) {
		$link = Linker::linkKnown(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$this->msg( 'globalblocking-return' )->escaped()
		);
		$out = $this->getOutput();
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->addWikiMsg( $successMsg, $ip, $id );
		$out->addHTML( $link );
		return true;
	}

	protected function getGroupName() {
		return 'users';
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}
}
