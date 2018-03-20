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

		$form = HTMLForm::factory( 'ooui', $fields, $context );
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
