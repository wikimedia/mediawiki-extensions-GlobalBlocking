<?php

class SpecialGlobalBlockList extends SpecialPage {
	public $mSearchIP, $mSearch;

	function __construct() {
		wfLoadExtensionMessages('GlobalBlocking');
		parent::__construct( 'GlobalBlockList' );
	}

	function execute( $par ) {
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
		} elseif ($action == 'whitelist') {
			$this->whitelistForm();
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
		$searchForm .= Xml::hidden( 'title',  SpecialPage::getTitleFor('GlobalBlockList')->getPrefixedText() );

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
		$searchForm .= GlobalBlocking::buildForm( $fields, 'globalblocking-search-submit' );

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
		$this->mWhitelistIP = $wgRequest->getVal( 'whitelistip' );
		$this->mWhitelistID = $wgRequest->getVal( 'whitelistid' );
		$this->mWhitelistStatus = $wgRequest->getVal( 'wpWhitelistStatus' );
		$this->mEditToken = $wgRequest->getVal( 'wpEditToken' );
		$this->mReason = $wgRequest->getVal( 'wpReason' );
	}
	
	function whitelistForm() {
		global $wgScript,$wgRequest,$wgUser,$wgOut;
		$errors = array();
		
		if (count(SpecialPage::getTitleFor( 'GlobalBlockList' )->getUserPermissionsErrors( 'globalblock-whitelist', $wgUser ))>1) {
			$this->displayRestrictionError();
			return;
		}
		
		if ($wgRequest->wasPosted() && $wgUser->matchEditToken( $this->mEditToken)) {
			$errors = $this->tryWhitelistSubmit();
			if (count($errors)==0)
				return;
		}
		
		$cur_status = (GlobalBlocking::getWhitelistInfo( $this->mWhitelistID ) == false) ? false : true;
		
		$wgOut->setSubTitle( wfMsg( 'globalblocking-whitelist-subtitle' ) );

		$form = '';

		$form .= Xml::openElement( 'fieldset' ) . Xml::element( 'legend', null, wfMsg( 'globalblocking-whitelist-legend' ) );
		$form .= Xml::openElement( 'form', array( 'method' => 'post', 'action' => $wgScript, 'name' => 'globalblock-whitelist' ) );

		$form .= Xml::hidden( 'title', SpecialPage::getTitleFor('GlobalBlockList')->getPrefixedText() );
		$form .= Xml::hidden( 'action', 'whitelist' );
		$form .= Xml::hidden( 'whitelistid', $this->mWhitelistID );

		$fields = array();

		$fields['ipaddress'] = wfInput( 'whitelistip', false, $this->mWhitelistIP, array( 'readonly' => 'readonly' ) );
		$fields['globalblocking-whitelist-reason'] = wfInput( 'wpReason', false, $this->mReason );
		$fields['globalblocking-whitelist-status'] = Xml::checkLabel( wfMsgExt( 'globalblocking-whitelist-statuslabel', 'parsemag' ), 'wpWhitelistStatus', 'wpWhitelistStatus', $cur_status );

		$form .= GlobalBlocking::buildForm( $fields, 'globalblocking-whitelist-submit' );

		$form .= Xml::hidden( 'wpEditToken', $wgUser->editToken() );

		$form .= Xml::closeElement( 'form' );
		$form .= Xml::closeElement( 'fieldset' );

		$wgOut->addHtml( $form );
	}
	
	function tryWhitelistSubmit() {
		global $wgOut,$wgUser;
		$ip = $this->mWhitelistIP;
		$id = $this->mWhitelistID;
		$new_status = $this->mWhitelistStatus;
		$cur_status = (GlobalBlocking::getWhitelistInfo( $id ) == false) ? false : true;
		
		// Already whitelisted.
		if ($cur_status == $new_status) {
			$this->showList( $successmsg );
			return array();
		}

		$dbw = wfGetDB( DB_MASTER );
		
		if ($new_status == true) {
			$gdbr = GlobalBlocking::getGlobalBlockingSlave();
			
			// Find the expiry of the block. This is important so that we can store it in the
			// global_block_whitelist table, which allows us to purge it when the block has expired.
			$expiry = $gdbr->selectField( 'globalblocks', 'gb_expiry', array( 'gb_id' => $id ), __METHOD__ );
			
			$row = array('gbw_by' => $wgUser->getId(), 'gbw_reason' => $this->mReason, 'gbw_expiry' => $expiry, 'gbw_id' => $id);
			$dbw->replace( 'global_block_whitelist', array( 'gbw_id' ), $row, __METHOD__ );

			$page = new LogPage( 'gblblock' );
			$page->addEntry( 'whitelist', SpecialPage::getTitleFor( 'Contributions', $ip ), $this->mReason );
			
			$successmsg = wfMsgExt( 'globalblocking-whitelist-whitelisted', array( 'parse' ), $ip, $id );
		} else {
			// Delete the row from the database
			$dbw->delete( 'global_block_whitelist', array( 'gbw_id' => $id ), __METHOD__ );
			
			$page = new LogPage( 'gblblock' );
			$page->addEntry( 'dewhitelist', SpecialPage::getTitleFor( 'Contributions', $ip ), $this->mReason );
			$successmsg = wfMsgExt( 'globalblocking-whitelist-dewhitelisted', array( 'parse' ), $ip, $id );
		}

		$wgOut->setSubtitle(wfMsg('globalblocking-whitelist-successsub'));

		$this->showList( $successmsg );

		return array();
	}

	function unblockForm() {
		global $wgScript,$wgRequest,$wgUser,$wgOut;
		$errors = array();
		if ($wgRequest->wasPosted() && $wgUser->matchEditToken( $this->mEditToken)) {
			$errors = $this->tryUnblockSubmit();
			if (count($errors)==0)
				return;
		}
		
		if (count(SpecialPage::getTitleFor( 'GlobalBlockList' )->getUserPermissionsErrors( 'globalunblock', $wgUser ))>1) {
			$this->displayRestrictionError();
			return;
		}
		
		$wgOut->setSubTitle( wfMsg( 'globalblocking-unblockform-subtitle' ) );

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

		$fields['ipaddress'] = wfInput( 'unblockip', false, $this->mUnblockIP );
		$fields['globalblocking-unblock-reason'] = wfInput( 'wpReason', false, $this->mReason );

		$form .= GlobalBlocking::buildForm( $fields, 'globalblocking-unblock-submit' );

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

		if (0==($id = GlobalBlocking::getGlobalBlockId( $ip ))) {
			$errors[] = array( 'globalblocking-unblock-notblocked', $ip );
		}

		if (count($errors)>0) {
			return $errors;
		}

		$dbw = GlobalBlocking::getGlobalBlockingMaster();

		$dbw->delete( 'globalblocks', array( 'gb_id' => $id ), __METHOD__ );

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
		$this->mDb = GlobalBlocking::getGlobalBlockingSlave();
	}

	function formatRow( $row ) {
		global $wgLang,$wgUser;

		$timestamp = $row->gb_timestamp;
		$expiry = $row->gb_expiry;
		$options = array();

		if (strlen($row->gb_reason)) {
			$options[] = $row->gb_reason;
		}

		$expiry = Block::decodeExpiry( $row->gb_expiry );
		if ($expiry == 'infinity') {
			$expiry = wfMsg( 'infiniteblock' );
		} else {
			global $wgLang;
			$expiry = $wgLang->timeanddate( wfTimestamp( TS_MW, $expiry ), true );
		}
		$options[] = wfMsg( 'globalblocking-list-expiry', $expiry);
		
		## Check for whitelisting.
		$wlinfo = GlobalBlocking::getWhitelistInfo( $row->gb_id );
		if ($wlinfo) {
			$options[] = wfMsg( 'globalblocking-list-whitelisted', User::whois($wlinfo['user']), $wlinfo['reason'] );
		}
		
		$timestamp = $wgLang->timeanddate( wfTimestamp( TS_MW, $timestamp ), true );

		if ($row->gb_anon_only)
			$options[] = wfMsg('globalblocking-list-anononly');

		$sk = $wgUser->getSkin();
		
		$titleObj = SpecialPage::getTitleFor( "GlobalBlockList" );

		$unblockLink = '';
		if (count(SpecialPage::getTitleFor( 'GlobalBlockList' )->getUserPermissionsErrors( 'globalunblock', $wgUser ))<=1 ) {
			$unblockLink = ' (' . $sk->makeKnownLinkObj($titleObj, wfMsg( 'globalblocking-list-unblock' ), 'action=unblock&unblockip=' . urlencode( $row->gb_address ) ) . ')';
		}
		
		if (count(SpecialPage::getTitleFor( 'GlobalBlockList' )->getUserPermissionsErrors( 'globalblock-whitelist', $wgUser ))<=1) {
			$whitelistLink = ' (' . $sk->makeKnownLinkObj($titleObj, wfMsg( 'globalblocking-list-whitelist' ), 'action=whitelist&whitelistip=' . urlencode( $row->gb_address ) . '&whitelistid=' . urlencode($row->gb_id) ) . ')';
		}

		return Xml::openElement( 'li' ) .
			wfMsgExt( 'globalblocking-list-blockitem', array( 'parseinline' ), $timestamp,
				$row->gb_by, $row->gb_by_wiki, $row->gb_address,
				implode( ', ', $options) ) . " $unblockLink $whitelistLink " .
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
