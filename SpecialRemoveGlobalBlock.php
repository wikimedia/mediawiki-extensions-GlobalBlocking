<?php

class SpecialRemoveGlobalBlock extends FormSpecialPage {
	function __construct() {
		parent::__construct( 'RemoveGlobalBlock', 'globalblock' );
	}

	function execute( $par ) {
		parent::execute( $par );
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'globalblocking-unblock' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->setArticleRelated( false );
		$out->enableClientCache( false );
	}

	function onSubmit( array $data ) {
		$errors = array();
		$ip = $data['ipaddress'];
		if ( !IP::isIPAddress( $ip ) && strlen( $ip ) ) {
			$errors[] = array( 'globalblocking-unblock-ipinvalid', $ip );
			$ip = '';
		}

		if ( ( $id = GlobalBlocking::getGlobalBlockId( $ip ) ) == 0 ) {
			$errors[] = array( 'globalblocking-notblocked', $ip );
		}

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		$out = $this->getOutput();
		$dbw = GlobalBlocking::getGlobalBlockingMaster();
		$dbw->delete( 'globalblocks', array( 'gb_id' => $id ), __METHOD__ );

		$page = new LogPage( 'gblblock' );
		$page->addEntry( 'gunblock', Title::makeTitleSafe( NS_USER, $ip ), $data['reason'] );

		$successmsg = $this->msg( 'globalblocking-unblock-unblocked', $ip, $id )->parseAsBlock();
		$out->addHTML( $successmsg );

		$link = Linker::linkKnown(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$this->msg( 'globalblocking-return' )->escaped()
		);
		$out->addHTML( $link );

		$out->setSubtitle( $this->msg( 'globalblocking-unblock-successsub' ) );

		return true;
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setWrapperLegendMsg( 'globalblocking-unblock-legend' );
		$form->setSubmitTextMsg( 'globalblocking-unblock-submit' );
		$form->setPreText( $this->msg( 'globalblocking-unblock-intro' )->parse() );
	}

	protected function getFormFields() {
		return array(
			'ipaddress' => array(
				'name' => 'address',
				'type' => 'text',
				'id' => 'mw-globalblocking-ipaddress',
				'label-message' => 'globalblocking-ipaddress',
				'default' => trim( $this->getRequest()->getText( 'address' ) ),
				'size' => 45,
			),
			'reason' => array(
				'name' => 'wpReason',
				'type' => 'text',
				'id' => 'mw-globalblocking-unblock-reason',
				'label-message' => 'globalblocking-unblock-reason',
				'default' => $this->getRequest()->getText( 'wpReason' ),
				'size' => 45
			),
		);
	}
}
