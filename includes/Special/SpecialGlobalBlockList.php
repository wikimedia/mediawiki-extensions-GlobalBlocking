<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use DerivativeContext;
use Html;
use HTMLForm;
use MediaWiki\Block\BlockUtils;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use SpecialPage;
use Wikimedia\IPUtils;

class SpecialGlobalBlockList extends SpecialPage {
	/** @var string|null */
	protected $target;

	/** @var array */
	protected $options;

	/** @var BlockUtils */
	private $blockUtils;

	public function __construct(
		BlockUtils $blockUtils
	) {
		parent::__construct( 'GlobalBlockList' );

		$this->blockUtils = $blockUtils;
	}

	/**
	 * @param string $par Parameters of the URL, probably the IP being actioned
	 * @return void
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader( 'globalblocking-list-intro' );
		$this->addHelpLink( 'Extension:GlobalBlocking' );

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'globalblocking-list' ) );
		$out->setSubtitle( GlobalBlocking::buildSubtitleLinks( $this ) );
		$out->setArticleRelated( false );
		$out->disableClientCache();

		$this->loadParameters( $par );
		$this->showForm();

		// Validate search target. If it is invalid, no need to build the pager.
		if ( $this->target && !IPUtils::isIPAddress( $this->target ) ) {
			$out->wrapWikiMsg(
				"<div class='error'>\n$1\n</div>",
				[ 'globalblocking-list-ipinvalid', $this->target ]
			);
			return;
		}

		$this->showList();
	}

	/**
	 * @param string|null $par Parameter from the URL, may be null or a string (probably an IP)
	 * that was inserted
	 */
	protected function loadParameters( $par ) {
		$request = $this->getRequest();
		$ip = trim( $request->getText( 'ip', $par ?? '' ) );
		if ( $ip !== '' ) {
			$ip = IPUtils::isIPAddress( $ip )
				? IPUtils::sanitizeRange( $ip )
				: $ip;
		}
		$this->target = $ip;

		$this->options = $request->getArray( 'wpOptions', [] );
	}

	protected function showForm() {
		$fields = [
			'ip' => [
				'type' => 'text',
				'name' => 'ip',
				'id' => 'mw-globalblocking-search-ip',
				'label-message' => 'globalblocking-search-ip',
				'default' => $this->target,
			],
			'Options' => [
				'type' => 'multiselect',
				'options-messages' => [
					'globalblocking-list-tempblocks' => 'tempblocks',
					'globalblocking-list-indefblocks' => 'indefblocks',
					'globalblocking-list-addressblocks' => 'addressblocks',
					'globalblocking-list-rangeblocks' => 'rangeblocks',
				],
				'flatlist' => true,
			],
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
		$dbr = GlobalBlocking::getGlobalBlockingDatabase( DB_REPLICA );

		// Build a list of blocks.
		$conds = [];

		if ( $this->target !== '' ) {
			[ $target, $type ] = $this->blockUtils->parseBlockTarget( $this->target );

			switch ( $type ) {
				case DatabaseBlock::TYPE_IP:
					$conds = GlobalBlocking::getRangeCondition( $target );
					break;
				case DatabaseBlock::TYPE_RANGE:
					$conds = [ 'gb_address' => $target ];
					break;
			}
		}

		$hideIP = in_array( 'addressblocks', $this->options );
		$hideRange = in_array( 'rangeblocks', $this->options );

		if ( $hideIP && $hideRange ) {
			$this->noResults();
			return;
		} elseif ( $hideIP ) {
			$conds[] = "gb_range_end > gb_range_start";
		} elseif ( $hideRange ) {
			$conds[] = "gb_range_end = gb_range_start";
		}

		$hideTemp = in_array( 'tempblocks', $this->options );
		$hideIndef = in_array( 'indefblocks', $this->options );
		if ( $hideTemp && $hideIndef ) {
			$this->noResults();
			return;
		} elseif ( $hideTemp ) {
			$conds[] = "gb_expiry = " . $dbr->addQuotes( $dbr->getInfinity() );
		} elseif ( $hideIndef ) {
			$conds[] = "gb_expiry != " . $dbr->addQuotes( $dbr->getInfinity() );
		}

		$pager = new GlobalBlockListPager( $this->getContext(), $conds, $this->getLinkRenderer() );
		$body = $pager->getBody();
		if ( $body != '' ) {
			$out->addHTML(
				$pager->getNavigationBar() .
				Html::rawElement( 'ul', [], $body ) .
				$pager->getNavigationBar()
			);
		} else {
			$this->noResults();
		}
	}

	/**
	 * Display an error when no results are found for those parameters
	 * @return void
	 */
	private function noResults() {
		$this->getOutput()->wrapWikiMsg(
			"<div class='mw-globalblocking-noresults'>\n$1</div>\n",
			[ 'globalblocking-list-noresults' ]
		);
	}

	protected function getGroupName() {
		return 'users';
	}
}
