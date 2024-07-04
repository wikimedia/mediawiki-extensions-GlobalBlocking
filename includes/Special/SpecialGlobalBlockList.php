<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use MediaWiki\Block\BlockUtils;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Extension\GlobalBlocking\GlobalBlocking;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\CentralId\CentralIdLookup;
use Wikimedia\IPUtils;

class SpecialGlobalBlockList extends SpecialPage {
	protected ?string $target;
	protected array $options;

	private BlockUtils $blockUtils;
	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;

	/**
	 * @param BlockUtils $blockUtils
	 * @param CommentFormatter $commentFormatter
	 * @param CentralIdLookup $lookup
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 * @param GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
	 */
	public function __construct(
		BlockUtils $blockUtils,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
	) {
		parent::__construct( 'GlobalBlockList' );

		$this->blockUtils = $blockUtils;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
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
		$out->setPageTitleMsg( $this->msg( 'globalblocking-list' ) );
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
		$dbr = GlobalBlocking::getReplicaGlobalBlockingDatabase();

		// Build a list of blocks.
		$conds = [];

		if ( $this->target !== '' ) {
			$conds[] = $this->globalBlockLookup->getRangeCondition( $this->target );
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
			$conds['gb_expiry'] = $dbr->getInfinity();
		} elseif ( $hideIndef ) {
			$conds[] = $dbr->expr( 'gb_expiry', '!=', $dbr->getInfinity() );
		}

		$pager = new GlobalBlockListPager(
			$this->getContext(),
			$conds,
			$this->getLinkRenderer(),
			$this->commentFormatter,
			$this->lookup,
			$this->globalBlockingLinkBuilder,
			$this->globalBlockingConnectionProvider,
			$this->globalBlockLocalStatusLookup
		);
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

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'users';
	}
}
