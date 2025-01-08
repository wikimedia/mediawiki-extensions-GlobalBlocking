<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Widget\HTMLUserTextFieldAllowingGlobalBlockIds;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\TempUser\TempUserConfig;
use MediaWiki\User\UserNameUtils;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\RawSQLExpression;

class SpecialGlobalBlockList extends FormSpecialPage {
	protected string $target;
	protected array $options;
	private array $conds;
	private bool $queryValid;

	private UserNameUtils $userNameUtils;
	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private TempUserConfig $tempUserConfig;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer;

	public function __construct(
		UserNameUtils $userNameUtils,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		TempUserConfig $tempUserConfig,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer
	) {
		parent::__construct( 'GlobalBlockList' );

		$this->userNameUtils = $userNameUtils;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->tempUserConfig = $tempUserConfig;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->globalBlockDetailsRenderer = $globalBlockDetailsRenderer;
	}

	/**
	 * @param string $par Parameters of the URL, probably the IP being actioned
	 * @return void
	 */
	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:GlobalBlocking' );

		$out = $this->getOutput();
		$out->setSubtitle( $this->globalBlockingLinkBuilder->buildSubtitleLinks( $this ) );
		$out->setArticleRelated( false );
		$out->disableClientCache();
	}

	/**
	 * @param string|null $par Parameter from the URL, may be null or a string
	 */
	protected function setParameter( $par ) {
		$request = $this->getRequest();
		// IP is a deprecated name for the target field, which is retained to not
		// break existing links specifying the 'ip' parameter a GET parameter.
		$target = trim( $request->getText( 'target', $request->getText( 'ip', $par ?? '' ) ) );

		if ( IPUtils::isValidRange( $target ) ) {
			$target = IPUtils::sanitizeRange( $target ) ?? $target;
		} elseif ( IPUtils::isValid( $target ) ) {
			$target = IPUtils::sanitizeIP( $target ) ?? $target;
		} else {
			$target = $this->userNameUtils->getCanonical( $target ) ?: $target;
		}
		$this->target = $target;

		$this->options = $request->getArray( 'wpOptions', [] );
	}

	/** @inheritDoc */
	protected function getFormFields() {
		$optionsMessages = [
			'globalblocking-list-tempblocks' => 'tempblocks',
			'globalblocking-list-indefblocks' => 'indefblocks',
			'globalblocking-list-autoblocks' => 'autoblocks',
			'globalblocking-list-userblocks' => 'userblocks',
			'globalblocking-list-addressblocks' => 'addressblocks',
			'globalblocking-list-rangeblocks' => 'rangeblocks',
		];

		if ( $this->tempUserConfig->isKnown() ) {
			$optionsMessages['globalblocking-list-tempaccountblocks'] = 'tempaccountblocks';
		}

		return [
			'target' => [
				'class' => HTMLUserTextFieldAllowingGlobalBlockIds::class,
				'ipallowed' => true,
				'iprange' => true,
				'iprangelimits' => $this->getConfig()->get( 'GlobalBlockingCIDRLimit' ),
				'name' => 'target',
				'id' => 'mw-globalblocking-search-target',
				'label-message' => 'globalblocking-target-with-block-ids',
				'default' => $this->target,
			],
			'Options' => [
				'type' => 'multiselect',
				'options-messages' => $optionsMessages,
				'flatlist' => true,
			],
		];
	}

	/** @inheritDoc */
	protected function alterForm( HTMLForm $form ) {
		$context = new DerivativeContext( $this->getContext() );
		 // remove subpage
		$context->setTitle( $this->getPageTitle() );
		$form->setName( 'globalblocklist-search' )
			->setSubmitTextMsg( 'globalblocking-search-submit' )
			->setWrapperLegendMsg( 'globalblocking-search-legend' )
			->setContext( $context );
	}

	/** @inheritDoc */
	public function onSubmit( array $data ) {
		$this->queryValid = true;
		// Build a list of blocks.
		$conds = [];

		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		if ( $this->target !== '' ) {
			$globalBlockId = GlobalBlockLookup::isAGlobalBlockId( $this->target );
			if ( $globalBlockId ) {
				$targetExpr = $dbr->expr( 'gb_id', '=', $globalBlockId );
			} else {
				if ( IPUtils::isIPAddress( $this->target ) ) {
					$ip = $this->target;
					$centralId = 0;
				} else {
					$ip = null;
					$centralId = $this->lookup->centralIdFromName( $this->target, $this->getAuthority() );
				}
				$targetExpr = $this->globalBlockLookup->getGlobalBlockLookupConditions(
					$ip, $centralId,
					GlobalBlockLookup::SKIP_ALLOWED_RANGES_CHECK | GlobalBlockLookup::SKIP_AUTOBLOCKS
				);
			}
			if ( $targetExpr === null ) {
				$this->queryValid = false;
				return Status::newFatal( 'nosuchusershort', $this->target );
			}
			$conds[] = $targetExpr;
		}

		$hideIP = in_array( 'addressblocks', $this->options );
		$hideRange = in_array( 'rangeblocks', $this->options );
		$hideUser = in_array( 'userblocks', $this->options );
		$hideAutoblocks = in_array( 'autoblocks', $this->options );
		$hideEverything = ( $hideIP && $hideRange && $hideUser && $hideAutoblocks );

		if ( $this->tempUserConfig->isKnown() ) {
			$hideTempAccounts = in_array( 'tempaccountblocks', $this->options );
			$hideEverything = ( $hideEverything && $hideTempAccounts );

			if ( $hideTempAccounts ) {
				$conds[] = $this->tempUserConfig->getMatchCondition(
					$dbr,
					'gb_address',
					IExpression::NOT_LIKE
				);
			}
		}

		if ( $hideEverything ) {
			$this->queryValid = false;
		}

		if ( $hideIP ) {
			$conds[] = $dbr->orExpr( [
				new RawSQLExpression( 'gb_range_end > gb_range_start' ),
				$dbr->expr( 'gb_target_central_id', '!=', 0 ),
			] );
		}
		if ( $hideRange ) {
			$conds[] = 'gb_range_end = gb_range_start';
		}
		if ( $hideUser ) {
			$conds['gb_target_central_id'] = 0;
		}
		if ( $hideAutoblocks ) {
			$conds['gb_autoblock_parent_id'] = 0;
		}

		$hideTempExpiryBlocks = in_array( 'tempblocks', $this->options );
		$hideIndefBlocks = in_array( 'indefblocks', $this->options );
		if ( $hideTempExpiryBlocks && $hideIndefBlocks ) {
			$this->queryValid = false;
		} elseif ( $hideTempExpiryBlocks ) {
			$conds['gb_expiry'] = $dbr->getInfinity();
		} elseif ( $hideIndefBlocks ) {
			$conds[] = $dbr->expr( 'gb_expiry', '!=', $dbr->getInfinity() );
		}
		$this->conds = $conds;

		return true;
	}

	/** @inheritDoc */
	public function onSuccess() {
		// If the form data was valid, then use the WHERE conditions generated from it to get a page of results.
		$pager = new GlobalBlockListPager(
			$this->getContext(),
			$this->conds,
			$this->getLinkRenderer(),
			$this->commentFormatter,
			$this->globalBlockingLinkBuilder,
			$this->globalBlockingConnectionProvider,
			$this->globalBlockDetailsRenderer
		);

		if ( $this->queryValid && $pager->getNumRows() ) {
			$this->getOutput()->addParserOutputContent( $pager->getFullOutput() );
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

	/** @inheritDoc */
	public function getShowAlways(): bool {
		return true;
	}

	/** @inheritDoc */
	protected function getDisplayFormat(): string {
		return 'ooui';
	}

	/** @inheritDoc */
	public function getDescription(): Message {
		return $this->msg( 'globalblocking-list' );
	}

	/** @inheritDoc */
	public function requiresPost(): bool {
		return false;
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'users';
	}
}
