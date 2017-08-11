<?php

class SpecialGlobalBlockList extends SpecialPage {
	/** @var string */
	protected $target;

	public function __construct() {
		parent::__construct( 'GlobalBlockList' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader( 'globalblocking-list-intro' );

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'globalblocking-list' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->setArticleRelated( false );
		$out->enableClientCache( false );

		$this->loadParameters( $this->getRequest()->getText( 'ip', $par ) );
		$this->showForm();

		// Validate search target. If it is invalid, no need to build the pager.
		if ( $this->target && !IP::isIPAddress( $this->target ) ) {
			$out->wrapWikiMsg(
				"<div class='error'>\n$1\n</div>",
				[ 'globalblocking-list-ipinvalid', $this->target ]
			);
			return;
		}

		$this->showList();
	}

	/**
	 * @param string $ip
	 */
	protected function loadParameters( $ip ) {
		$ip = trim( $ip );
		if ( $ip !== '' ) {
			$ip = IP::isIPAddress( $ip )
				? IP::sanitizeRange( $ip )
				: $ip;
		}
		$this->target = $ip;
	}

	protected function showForm() {
		$fields = [
			'ip' => [
				'type' => 'text',
				'name' => 'ip',
				'id' => 'mw-globalblocking-search-ip',
				'label-message' => 'globalblocking-search-ip',
				'default' => $this->target,
			]
		];
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

	protected function showList() {
		$out = $this->getOutput();

		// Build a list of blocks.
		$conds = [];
		$ip = $this->target;

		if ( $ip ) {
			list( $rangeStart, $rangeEnd ) = IP::parseRange( $ip );

			if ( $rangeStart === $rangeEnd ) {
				// They searched for an IP. Match any range covering that IP
				$conds = GlobalBlocking::getRangeCondition( $ip );
			} else {
				// They searched for a range. Match that exact range only
				$conds = [ 'gb_address' => $ip ];
			}
		}

		$pager = new GlobalBlockListPager( $this->getContext(), $conds );
		$body = $pager->getBody();
		if ( $body != '' ) {
			$out->addHTML(
				$pager->getNavigationBar() .
				Html::rawElement( 'ul', [], $body ) .
				$pager->getNavigationBar()
			);
		} else {
			$out->wrapWikiMsg(
				"<div class='mw-globalblocking-noresults'>\n$1</div>\n",
				[ 'globalblocking-list-noresults' ]
			);
		}
	}

	protected function getGroupName() {
		return 'users';
	}
}

class GlobalBlockListPager extends ReverseChronologicalPager {
	/** @var array */
	private $queryConds;

	public function __construct( IContextSource $context, array $conds ) {
		parent::__construct( $context );
		$this->queryConds = $conds;
		$this->mDb = GlobalBlocking::getGlobalBlockingDatabase( DB_SLAVE );
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
