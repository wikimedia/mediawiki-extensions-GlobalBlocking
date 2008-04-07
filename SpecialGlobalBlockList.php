<?php

class SpecialGlobalBlockList extends SpecialPage {
	public $mSearchIP, $mSearch;

	function __construct() {
		wfLoadExtensionMessages('GlobalBlocking');
		parent::__construct( 'GlobalBlockList' );
	}

	function execute() {
		global $wgUser,$wgOut,$wgRequest;

		$this->setHeaders();
		$this->loadParameters();

		$wgOut->setPageTitle( wfMsg( 'globalblocking-list' ) );
		$wgOut->setRobotpolicy( "noindex,nofollow" );
		$wgOut->setArticleRelated( false );
		$wgOut->enableClientCache( false );

		$action = $this->mAction;

		if ($action == 'unblock') {
			$this->unblockForm();
		} else {
			$this->showList();
		}
	}

	function showList( $pretext = '' ) {
		global $wgOut,$wgScript;
		$errors = array();

		// Validate search IP
		$ip = $this->mSearchIP;
		if (!(IP::isIPv4($ip) || IP::isIPv6($ip)) && strlen($ip)) {
			$errors[] = array('globalblocking-list-ipinvalid',$ip);
			$ip = '';
		}

		$wgOut->addHtml( $pretext );

		// Build the search form
		$searchForm = '';
		$searchForm .= Xml::openElement( 'fieldset' ) .
			Xml::element( 'legend', null, wfMsg( 'globalblocking-search-legend' ) );
		$searchForm .= Xml::openElement( 'form', array( 'method' => 'post', 'action' => $wgScript, 'name' => 'globalblocklist-search' ) );
		$searchform .= Xml::hidden( 'title',  SpecialPage::getTitleFor('GlobalBlockList')->getPrefixedText() );

		if (count($errors)>0) {
			$errorstr = '';
			foreach ( $errors as $error ) {
				$errorstr .= '* ' . call_user_func_array('wfMsgHtml', $error)."\n";
			}

			$searchForm .= Xml::openElement( 'div', array( 'class' => 'error' ) ) .
				wfMsgExt( 'globalblocking-search-errors', array( 'parse'), $errorstr ) . Xml::closeElement( 'div' );
		}

		$fields = array();
		$fields['globalblocking-search-ip'] = wfInput( 'wpSearchIP', false, $ip );
		$searchForm .= gbBuildForm( $fields, 'globalblocking-search-submit' );

		$searchForm .= Xml::hidden( 'wpSearch', 1 );
		$searchForm .= Xml::closeElement( 'form' ) . Xml::closeElement( 'fieldset' );
		$wgOut->addHtml( $searchForm );

		// Build a list of blocks.
		$conds = array();
		if (strlen($ip)) {
			$conds['gb_address'] = $ip;
		}

		$pager = new GlobalBlockListPager( $this, $conds );

		$wgOut->addHtml( $pager->getNavigationBar() .
				Xml::tags( 'ul', null, $pager->getBody() ) .
				$pager->getNavigationBar() );
	}

	function loadParameters() {
		global $wgRequest,$wgUser;
		$this->mSearchIP = $wgRequest->getVal( 'wpSearchIP' );
		$this->mSearch = $wgRequest->getCheck( 'wpSearch' );
		$this->mAction = $wgRequest->getVal( 'action', 'list' );
		$this->mUnblockIP = $wgRequest->getVal( 'unblockip' );
		$this->mEditToken = $wgRequest->getVal( 'wpEditToken' );
		$this->mReason = $wgRequest->getVal( 'wpReason' );
	}

	function unblockForm() {
		global $wgScript,$wgRequest,$wgUser,$wgOut;
		$errors = array();
		if ($wgRequest->wasPosted() && $wgUser->matchEditToken( $this->mEditToken)) {
			$errors = $this->tryUnblockSubmit();
			if (count($errors)==0)
				return;
		}

		$form = '';

		if (count($errors)>0) {
			$errorstr = '';
			foreach ( $errors as $error ) {
				$errorstr .= '* ' . call_user_func_array('wfMsgHtml', $error)."\n";
			}

			$form .= Xml::openElement( 'div', array( 'class' => 'error' ) ) .
				wfMsgExt( 'globalblocking-unblock-errors', array( 'parse'), $errorstr ) . Xml::closeElement( 'div' );
		}

		$form .= Xml::openElement( 'fieldset' ) . Xml::element( 'legend', null, wfMsg( 'globalblocking-unblock-legend' ) );
		$form .= Xml::openElement( 'form', array( 'method' => 'post', 'action' => $wgScript, 'name' => 'globalblock-unblock' ) );

		$form .= Xml::hidden( 'title', SpecialPage::getTitleFor('GlobalBlockList')->getPrefixedText() );
		$form .= Xml::hidden( 'action', 'unblock' );

		$fields = array();

		$fields['globalblocking-unblock-ipaddress'] = wfInput( 'unblockip', false, $this->mUnblockIP );
		$fields['globalblocking-unblock-reason'] = wfInput( 'wpReason', false, $this->mReason );

		$form .= gbBuildForm( $fields, 'globalblocking-unblock-submit' );

		$form .= Xml::hidden( 'wpEditToken', $wgUser->editToken() );

		$form .= Xml::closeElement( 'form' );
		$form .= Xml::closeElement( 'fieldset' );

		$wgOut->addHtml( $form );
	}

	function tryUnblockSubmit() {
		global $wgOut,$wgUser;
		$errors = array();
		$ip = $this->mUnblockIP;
		if (!(IP::isIPv4($ip) || IP::isIPv6($ip)) && strlen($ip)) {
			$errors[] = array('globalblocking-unblock-ipinvalid',$ip);
			$ip = '';
		}

		if (0==($id = gbGetGlobalBlockId( $ip ))) {
			$errors[] = array( 'globalblocking-unblock-notblocked', $ip );
		}

		if (count($errors)>0) {
			return $errors;
		}

		$dbw = gbGetGlobalBlockingMaster();

		$dbw->delete( 'globalblocks', array( 'gb_id' => $id ) );

		$page = new LogPage( 'gblblock' );

		$page->addEntry( 'gunblock', SpecialPage::getTitleFor( 'Contributions', $ip ), $this->mReason );

		$successmsg = wfMsgExt( 'globalblocking-unblock-unblocked', array( 'parse' ), $ip, $id );

		$wgOut->setSubtitle(wfMsg('globalblocking-unblock-successsub'));

		$this->showList( $successmsg );

		return array();
	}
}

// Shamelessly stolen from SpecialIpblocklist.php
class GlobalBlockListPager extends ReverseChronologicalPager {
	public $mForm, $mConds;

	function __construct( $form, $conds = array() ) {
		$this->mForm = $form;
		$this->mConds = $conds;
		parent::__construct();
		$this->mDb = gbGetGlobalBlockingSlave();
	}

	function formatRow( $row ) {
		global $wgLang,$wgUser;

		$timestamp = $row->gb_timestamp;
		$expiry = $row->gb_expiry;
		$options = array();

		if (strlen($row->gb_reason)) {
			$options[] = $row->gb_reason;
		}

		$expiry = Block::decodeExpiry( $block->gb_expiry );
		if ($expiry == 'infinity') {
			$expiry = wfMsg( 'infiniteblock' );
		} else {
			global $wgLang;
			$expiry = $wgLang->timeanddate( wfTimestamp( TS_MW, $expiry ), true );
		}
		$options[] = wfMsg( 'globalblocking-list-expiry', $expiry);

		$timestamp = $wgLang->timeanddate( wfTimestamp( TS_MW, $timestamp ), true );

		if ($row->gb_anon_only)
			$options[] = wfMsg('globalblocking-list-anononly');

		$sk = $wgUser->getSkin();

		$unblocklink = '';
		if (count(SpecialPage::getTitleFor( 'GlobalBlockList' )->getUserPermissionsErrors( 'globalunblock', $wgUser ))<=1 ) {
			$titleObj = SpecialPage::getTitleFor( "GlobalBlockList" );
			$unblockLink = ' (' . $sk->makeKnownLinkObj($titleObj, wfMsg( 'globalblocking-list-unblock' ), 'action=unblock&unblockip=' . urlencode( $row->gb_address ) ) . ')';
		}

		return Xml::openElement( 'li' ) .
			wfMsgExt( 'globalblocking-list-blockitem', array( 'parseinline' ), $timestamp,
				$row->gb_by, $row->gb_by_wiki, $row->gb_address,
				implode( ', ', $options) ) . " $unblockLink" .
			Xml::closeElement( 'li' );
	}

	function getQueryInfo() {
		$conds = $this->mConds;
		#$conds[] = 'gb_expiry>' . $this->mDb->addQuotes( $this->mDb->timestamp() );
		return array(
			'tables' => 'globalblocks',
			'fields' => '*',
			'conds' => $conds,
		);
	}

	function getIndexField() {
		return 'gb_timestamp';
	}
}
