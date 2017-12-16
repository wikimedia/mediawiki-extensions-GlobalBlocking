<?php

class GlobalBlockListPager extends ReverseChronologicalPager {
	/** @var array */
	private $queryConds;

	public function __construct( IContextSource $context, array $conds ) {
		parent::__construct( $context );
		$this->queryConds = $conds;
		$this->mDb = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );
	}

	public function formatRow( $row ) {
		global $wgApplyGlobalBlocks;

		$lang = $this->getLanguage();
		$options = [];

		$expiry = $lang->formatExpiry( $row->gb_expiry, TS_MW );
		if ( $expiry == 'infinity' ) {
			$options[] = $this->msg( 'infiniteblock' )->parse();
		} else {
			$options[] = $this->msg(
				'expiringblock',
				$lang->date( $expiry ),
				$lang->time( $expiry )
			)->parse();
		}

		// Check for whitelisting.
		$wlinfo = GlobalBlocking::getWhitelistInfo( $row->gb_id );
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
		$user = $this->getUser();
		$canBlock = $user->isAllowed( 'globalblock' );
		if ( $canBlock ) {
			$info[] = Linker::linkKnown(
				SpecialPage::getTitleFor( 'RemoveGlobalBlock' ),
				$this->msg( 'globalblocking-list-unblock' )->parse(),
				[],
				[ 'address' => $row->gb_address ]
			);
		}

		if ( $wgApplyGlobalBlocks && $user->isAllowed( 'globalblock-whitelist' ) ) {
			$info[] = Linker::link(
				SpecialPage::getTitleFor( 'GlobalBlockStatus' ),
				$this->msg( 'globalblocking-list-whitelist' )->parse(),
				[],
				[ 'address' => $row->gb_address ]
			);
		}

		if ( $canBlock ) {
			$info[] = Linker::linkKnown(
				SpecialPage::getTitleFor( 'GlobalBlock' ),
				$this->msg( 'globalblocking-list-modify' )->parse(),
				[],
				[ 'wpAddress' => $row->gb_address ]
			);
		}

		$timestamp = $row->gb_timestamp;
		$timestamp = $lang->timeanddate( wfTimestamp( TS_MW, $timestamp ), true );
		// Userpage link / Info on originating wiki
		$displayWiki = WikiMap::getWikiName( $row->gb_by_wiki );
		$userDisplay = GlobalBlocking::maybeLinkUserpage( $row->gb_by_wiki, $row->gb_by );
		$infoItems = count( $info )
			? $this->msg( 'parentheses', $lang->pipeList( $info ) )->text()
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
				Linker::commentBlock( $row->gb_reason ) . ' ' .
				$infoItems
		);
	}

	public function getQueryInfo() {
		return [
			'tables' => 'globalblocks',
			'fields' => '*',
			'conds' => $this->queryConds,
		];
	}

	public function getIndexField() {
		return 'gb_timestamp';
	}
}
