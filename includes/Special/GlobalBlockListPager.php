<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use CentralIdLookup;
use IContextSource;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\ReverseChronologicalPager;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;

class GlobalBlockListPager extends ReverseChronologicalPager {
	private array $queryConds;

	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	/**
	 * @param IContextSource $context
	 * @param array $conds
	 * @param LinkRenderer $linkRenderer
	 * @param CommentFormatter $commentFormatter
	 * @param CentralIdLookup $lookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 */
	public function __construct(
		IContextSource $context,
		array $conds,
		LinkRenderer $linkRenderer,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		// Set database before parent constructor so that the DB that has the globalblocks table is used
		// over the local database which may not be the same database.
		$this->mDb = GlobalBlocking::getReplicaGlobalBlockingDatabase();
		parent::__construct( $context, $linkRenderer );
		$this->queryConds = $conds;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
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
		$timestamp = $row->gb_timestamp;
		$timestamp = $lang->userTimeAndDate( wfTimestamp( TS_MW, $timestamp ), $user );
		// Userpage link / Info on originating wiki
		$displayWiki = WikiMap::getWikiName( $row->gb_by_wiki );
		$userDisplay = GlobalBlocking::maybeLinkUserpage(
			$row->gb_by_wiki,
			$this->lookup->nameFromCentralId( $row->gb_by_central_id ) ?? ''
		);
		$infoItems = $this->globalBlockingLinkBuilder->getActionLinks( $user, $row->gb_address );

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
