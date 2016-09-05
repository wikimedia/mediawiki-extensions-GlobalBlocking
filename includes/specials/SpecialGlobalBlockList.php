<?php

class SpecialGlobalBlockList extends SpecialPage {
	public $mSearchIP, $mSearch;

	function __construct() {
		parent::__construct( 'GlobalBlockList' );
	}

	function execute( $par ) {
		$out = $this->getOutput();
		$this->setHeaders();
		$this->outputHeader( 'globalblocking-list-intro' );
		$ip = isset( $par ) ? $par : $this->getRequest()->getText( 'ip' );
		$this->loadParameters( $ip );

		$out->setPageTitle( $this->msg( 'globalblocking-list' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->setArticleRelated( false );
		$out->enableClientCache( false );

		$this->showForm();

		// Validate search target. If it is invalid, no need to build the pager.
		if ( $this->mSearchIP && !IP::isIPAddress( $this->mSearchIP ) ) {
			$out->wrapWikiMsg(
				"<div class='error'>\n$1\n</div>",
				array( 'globalblocking-list-ipinvalid', $this->mSearchIP )
			);
			return;
		}

		$this->showList();
	}

	protected function showForm() {
		$fields = array(
			'ip' => array(
				'type' => 'text',
				'name' => 'ip',
				'id' => 'mw-globalblocking-search-ip',
				'label-message' => 'globalblocking-search-ip',
				'default' => $this->mSearchIP,
			)
		);
		$context = new DerivativeContext( $this->getContext() );
		$context->setTitle( $this->getPageTitle() ); // remove subpage

		$form = HTMLForm::factory( 'table', $fields, $context );
		$form->setMethod( 'get' )
			->setName( 'globalblocklist-search' )
			->setSubmitTextMsg( 'globalblocking-search-submit' )
			->setWrapperLegendMsg( 'globalblocking-search-legend' )
			->prepareForm()
			->displayForm( false );
	}

	function showList() {
		$out = $this->getOutput();

		// Build a list of blocks.
		$conds = array();
		$ip = $this->mSearchIP;

		if ( $ip ) {
			list ( $range_start, $range_end ) = IP::parseRange( $ip );

			if ( $range_start != $range_end ) {
				// They searched for a range. Match that exact range only
				$conds = array( 'gb_address' => $ip );
			} else {
				// They searched for an IP. Match any range covering that IP
				$conds = GlobalBlocking::getRangeCondition( $ip );
			}
		}

		$pager = new GlobalBlockListPager( $this, $conds );
		$body = $pager->getBody();
		if ( $body != '' ) {
			$out->addHTML( $pager->getNavigationBar() .
				Html::rawElement( 'ul', array(), $body ) .
				$pager->getNavigationBar() );
		} else {
			$out->wrapWikiMsg(
				"<div class='mw-globalblocking-noresults'>\n$1</div>\n",
				array( 'globalblocking-list-noresults' )
			);
		}
	}

	function loadParameters( $ip ) {
		$ip = trim( $ip );
		$this->mSearchIP = ( $ip !== '' )
			? ( IP::isIPAddress( $ip ) ? IP::sanitizeRange( $ip ) : $ip)
			: '';
	}

	protected function getGroupName() {
		return 'users';
	}
}

// Shamelessly stolen from SpecialIpblocklist.php
class GlobalBlockListPager extends ReverseChronologicalPager {
	public $mForm, $mConds;

	function __construct( $form, $conds = array() ) {
		$this->mForm = $form;
		$this->mConds = $conds;
		parent::__construct();
		$this->mDb = GlobalBlocking::getGlobalBlockingDatabase( DB_SLAVE );
	}

	function formatRow( $row ) {
		## Setup
		$timestamp = $row->gb_timestamp;
		$expiry = $this->getLanguage()->formatExpiry( $row->gb_expiry, TS_MW );
		$options = array();

		if ( $expiry == 'infinity' ) {
			$options[] = $this->msg( 'infiniteblock' )->parse();
		} else {
			$options[] = $this->msg(
				'expiringblock',
				$this->getLanguage()->date( $expiry ),
				$this->getLanguage()->time( $expiry )
			)->parse();
		}

		# Check for whitelisting.
		$wlinfo = GlobalBlocking::getWhitelistInfo( $row->gb_id );
		if ( $wlinfo ) {
			$options[] = $this->msg(
				'globalblocking-list-whitelisted',
				User::whois( $wlinfo['user'] ), $wlinfo['reason']
			)->text();
		}

		$timestamp = $this->getLanguage()->timeanddate( wfTimestamp( TS_MW, $timestamp ), true );

		if ( $row->gb_anon_only ) {
			$options[] = $this->msg( 'globalblocking-list-anononly' )->text();
		}

		## Do afterthoughts (comment, links for admins)
		$info = array();
		$canBlock = $this->getUser()->isAllowed( 'globalblock' );
		if ( $canBlock ) {
			$unblockTitle = SpecialPage::getTitleFor( "RemoveGlobalBlock" );
			$info[] = Linker::link( $unblockTitle,
				$this->msg( 'globalblocking-list-unblock' )->parse(),
				array(),
				array( 'address' => $row->gb_address )
			);
		}

		global $wgApplyGlobalBlocks;
		if ( $this->getUser()->isAllowed( 'globalblock-whitelist' ) && $wgApplyGlobalBlocks ) {
			$whitelistTitle = SpecialPage::getTitleFor( "GlobalBlockStatus" );
			$info[] = Linker::link( $whitelistTitle,
				$this->msg( 'globalblocking-list-whitelist' )->parse(),
				array(),
				array( 'address' => $row->gb_address )
			);
		}

		if ( $canBlock ) {
			$reblockTitle = SpecialPage::getTitleFor( 'GlobalBlock' );
			$msg = $this->msg( 'globalblocking-list-modify' )->parse();
			$info[] = Linker::link(
				$reblockTitle,
				$msg,
				array(),
				array( 'wpAddress' => $row->gb_address )
			);
		}

		## Userpage link / Info on originating wiki
		$display_wiki = WikiMap::getWikiName( $row->gb_by_wiki );
		$user_display = GlobalBlocking::maybeLinkUserpage( $row->gb_by_wiki, $row->gb_by );
		$infoItems = count( $info ) ?
			$this->msg( 'parentheses', $this->getLanguage()->pipeList( $info ) )->text() :
			'';

		## Put it all together.
		return Html::rawElement( 'li', array(),
			$this->msg( 'globalblocking-list-blockitem',
				$timestamp,
				$user_display,
				$display_wiki,
				$row->gb_address,
				$this->getLanguage()->commaList( $options )
			)->parse() . ' ' .
				Linker::commentBlock( $row->gb_reason ) . ' ' .
				$infoItems
		);
	}

	function getQueryInfo() {
		$conds = $this->mConds;
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
