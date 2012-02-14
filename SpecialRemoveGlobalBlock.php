<?php

class SpecialRemoveGlobalBlock extends SpecialPage {
	public $mAddress, $mReason;

	function __construct() {
		parent::__construct( 'RemoveGlobalBlock', 'globalunblock' );
	}

	function execute( $par ) {
		global $wgUser;
		$this->setHeaders();

		$this->loadParameters();

		$out = $this->getOutput();

		$out->setPageTitle( wfMsg( 'globalblocking-unblock' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( 'RemoveGlobalBlock' ) );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->setArticleRelated( false );
		$out->enableClientCache( false );

		if (!$this->userCanExecute( $wgUser )) {
			$this->displayRestrictionError();
			return;
		}

		$errors = '';

		$request = $this->getRequest();
		if ( $request->wasPosted() && $wgUser->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			// They want to submit. Let's have a look.
			$errors = $this->trySubmit();
			if( !$errors ) {
				// Success!
				return;
			}
		}

		$out->addWikiMsg( 'globalblocking-unblock-intro' );

		if (is_array($errors) && count($errors)>0) {
			$errorstr = '';

			foreach ( $errors as $error ) {
				if (is_array($error)) {
					$msg = array_shift($error);
				} else {
					$msg = $error;
					$error = array();
				}
				$errorstr .= Xml::tags( 'li', null, wfMsgExt( $msg, array( 'parseinline' ), $error ) );
			}

			$errorstr = Xml::tags( 'ul', array( 'class' => 'error' ), $errorstr );
			$errorstr = wfMsgExt( 'globalblocking-unblock-errors', array('parse'), array( count( $errors ) ) ) . $errorstr;
			$errorstr = Xml::tags( 'div', array( 'class' => 'error' ), $errorstr );
			$out->addHTML( $errorstr );
		}

		$this->form();
	}

	function loadParameters() {
		global $wgRequest;
		$this->mUnblockIP = trim($wgRequest->getText( 'address' ));
		$this->mReason = $wgRequest->getText( 'wpReason' );
		$this->mEditToken = $wgRequest->getText( 'wpEditToken' );
	}

	function trySubmit() {
		global $wgOut;
		$errors = array();
		$ip = $this->mUnblockIP;
		if (!IP::isIPAddress($ip) && strlen($ip)) {
			$errors[] = array('globalblocking-unblock-ipinvalid',$ip);
			$ip = '';
		}

		if (0==($id = GlobalBlocking::getGlobalBlockId( $ip ))) {
			$errors[] = array( 'globalblocking-notblocked', $ip );
		}

		if (count($errors)>0) {
			return $errors;
		}

		$out = $this->getOutput();
		$dbw = GlobalBlocking::getGlobalBlockingMaster();
		$dbw->delete( 'globalblocks', array( 'gb_id' => $id ), __METHOD__ );

		$page = new LogPage( 'gblblock' );
		$page->addEntry( 'gunblock', Title::makeTitleSafe( NS_USER, $ip ), $this->mReason );

		$successmsg = wfMsgExt( 'globalblocking-unblock-unblocked', array( 'parse' ), $ip, $id );
		$out->addHTML( $successmsg );

		$link = Linker::makeKnownLinkObj( SpecialPage::getTitleFor( 'GlobalBlockList' ), wfMsg( 'globalblocking-return' ) );
		$out->addHTML( $link );

		$out->setSubtitle(wfMsg('globalblocking-unblock-successsub'));

		return array();
	}

	function form( ) {
		global $wgScript, $wgUser, $wgOut;

		$form = '';

		$form .= Xml::openElement( 'fieldset' ) . Xml::element( 'legend', null, wfMsg( 'globalblocking-unblock-legend' ) );
		$form .= Xml::openElement( 'form', array( 'method' => 'post', 'action' => $wgScript, 'name' => 'globalblock-unblock' ) );

		$form .= Html::hidden( 'title', $this->getTitle()->getPrefixedText() );
		$form .= Html::hidden( 'action', 'unblock' );

		$fields = array();

		$fields['globalblocking-ipaddress'] = Xml::input( 'address', 45, $this->mUnblockIP );
		$fields['globalblocking-unblock-reason'] = Xml::input( 'wpReason', 45, $this->mReason );

		$form .= Xml::buildForm( $fields, 'globalblocking-unblock-submit' );

		$form .= Html::hidden( 'wpEditToken', $wgUser->editToken() );

		$form .= Xml::closeElement( 'form' );
		$form .= Xml::closeElement( 'fieldset' );

		$wgOut->addHTML( $form );
	}
}
