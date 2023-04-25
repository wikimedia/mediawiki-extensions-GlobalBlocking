<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use Html;
use HtmlArmor;
use IContextSource;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\WikiMap\WikiMap;
use ReverseChronologicalPager;
use SpecialPage;
use User;

class GlobalBlockListPager extends ReverseChronologicalPager {
	/** @var array */
	private $queryConds;

	/** @var CommentFormatter */
	private $commentFormatter;

	public function __construct(
		IContextSource $context,
		array $conds,
		LinkRenderer $linkRenderer,
		CommentFormatter $commentFormatter
	) {
		// Set database before parent constructor to avoid setting it there with wfGetDB
		$this->mDb = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );
		parent::__construct( $context, $linkRenderer );
		$this->queryConds = $conds;
		$this->commentFormatter = $commentFormatter;
	}

	public function formatRow( $row ) {
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$options = [];

		$expiry = $lang->formatExpiry( $row->gb_expiry, TS_MW );
		if ( $expiry == 'infinity' ) {
			$options[] = $this->msg( 'globalblocking-infiniteblock' )->parse();
		} else {
			$options[] = $this->msg(
				'globalblocking-expiringblock',
				$lang->userDate( $expiry, $user ),
				$lang->userTime( $expiry, $user )
			)->parse();
		}

		// Check for whitelisting.
		$wlinfo = GlobalBlocking::getLocalWhitelistInfo( $row->gb_id );
		if ( $wlinfo ) {
			$options[] = $this->msg(
				'globalblocking-list-whitelisted',
				User::whois( $wlinfo['user'] ), $wlinfo['reason']
			)->text();
		}

		if ( $row->gb_anon_only ) {
			$options[] = $this->msg( 'globalblocking-list-anononly' )->text();
		}

		// Do afterthoughts (comment, links for admins)
		$info = [];
		$canBlock = $user->isAllowed( 'globalblock' );
		if ( $canBlock ) {
			$info[] = $this->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'RemoveGlobalBlock' ),
				new HtmlArmor( $this->msg( 'globalblocking-list-unblock' )->parse() ),
				[],
				[ 'address' => $row->gb_address ]
			);
		}

		if ( $this->getConfig()->get( 'ApplyGlobalBlocks' )
				&& $user->isAllowed( 'globalblock-whitelist' ) ) {
			$info[] = $this->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlockStatus' ),
				new HtmlArmor( $this->msg( 'globalblocking-list-whitelist' )->parse() ),
				[],
				[ 'address' => $row->gb_address ]
			);
		}

		if ( $canBlock ) {
			$info[] = $this->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlock' ),
				new HtmlArmor( $this->msg( 'globalblocking-list-modify' )->parse() ),
				[],
				[ 'wpAddress' => $row->gb_address ]
			);
		}

		$timestamp = $row->gb_timestamp;
		$timestamp = $lang->userTimeAndDate( wfTimestamp( TS_MW, $timestamp ), $user );
		// Userpage link / Info on originating wiki
		$displayWiki = WikiMap::getWikiName( $row->gb_by_wiki );
		$userDisplay = GlobalBlocking::maybeLinkUserpage( $row->gb_by_wiki, $row->gb_by );
		$infoItems = count( $info )
			? $this->msg( 'parentheses' )->rawParams( $lang->pipeList( $info ) )->escaped()
			: '';

		// Put it all together.
		return Html::rawElement( 'li', [],
			$this->msg( 'globalblocking-list-blockitem',
				$timestamp,
				$userDisplay,
				$displayWiki,
				$row->gb_address,
				$lang->commaList( $options )
			)->parse() . ' ' .
				$this->commentFormatter->formatBlock( $row->gb_reason ) . ' ' .
				$infoItems
		);
	}

	public function getQueryInfo() {
		return [
			'tables' => 'globalblocks',
			'fields' => GlobalBlocking::selectFields(),
			'conds' => $this->queryConds,
		];
	}

	public function getIndexField() {
		return 'gb_timestamp';
	}
}
