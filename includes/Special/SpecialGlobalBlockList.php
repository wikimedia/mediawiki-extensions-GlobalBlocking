<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserNameUtils;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IReadableDatabase;

class SpecialGlobalBlockList extends FormSpecialPage {
	protected string $target;
	protected array $options;
	private array $conds;
	private bool $queryValid;

	private UserNameUtils $userNameUtils;
	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup;

	/**
	 * @param UserNameUtils $userNameUtils
	 * @param CommentFormatter $commentFormatter
	 * @param CentralIdLookup $lookup
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 * @param GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	 */
	public function __construct(
		UserNameUtils $userNameUtils,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	) {
		parent::__construct( 'GlobalBlockList' );

		$this->userNameUtils = $userNameUtils;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockingUserVisibilityLookup = $globalBlockingUserVisibilityLookup;
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

	protected function getFormFields() {
		$optionsMessages = [
			'globalblocking-list-tempblocks' => 'tempblocks',
			'globalblocking-list-indefblocks' => 'indefblocks',
			'globalblocking-list-addressblocks' => 'addressblocks',
			'globalblocking-list-rangeblocks' => 'rangeblocks',
		];
		$accountBlocksEnabled = $this->getConfig()->get( 'GlobalBlockingAllowGlobalAccountBlocks' );
		if ( $accountBlocksEnabled ) {
			$optionsMessages['globalblocking-list-userblocks'] = 'userblocks';
		}
		return [
			'target' => [
				// If global account blocks are not enabled, then
				'type' => $accountBlocksEnabled ? 'user' : 'text',
				'ipallowed' => true,
				'iprange' => true,
				'iprangelimits' => $this->getConfig()->get( 'GlobalBlockingCIDRLimit' ),
				'name' => 'target',
				'id' => 'mw-globalblocking-search-ip',
				'label-message' => $accountBlocksEnabled ? 'globalblocking-search-target' : 'globalblocking-search-ip',
				'default' => $this->target,
			],
			'Options' => [
				'type' => 'multiselect',
				'options-messages' => $optionsMessages,
				'flatlist' => true,
			],
		];
	}

	protected function alterForm( HTMLForm $form ) {
		$context = new DerivativeContext( $this->getContext() );
		$context->setTitle( $this->getPageTitle() ); // remove subpage
		$form->setName( 'globalblocklist-search' )
			->setSubmitTextMsg( 'globalblocking-search-submit' )
			->setWrapperLegendMsg( 'globalblocking-search-legend' )
			->setContext( $context );
	}

	public function onSubmit( array $data ) {
		$this->queryValid = true;
		// Build a list of blocks.
		$conds = [];

		if ( $this->target !== '' ) {
			if ( IPUtils::isIPAddress( $this->target ) ) {
				$ip = $this->target;
				$centralId = 0;
			} elseif ( $this->getConfig()->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
				$ip = null;
				$centralId = $this->lookup->centralIdFromName( $this->target, $this->getAuthority() );
			} else {
				$this->queryValid = false;
				return Status::newFatal( 'badipaddress' );
			}
			$targetExpr = $this->globalBlockLookup->getGlobalBlockLookupConditions(
				$ip, $centralId, GlobalBlockLookup::SKIP_ALLOWED_RANGES_CHECK
			);
			if ( $targetExpr === null ) {
				$this->queryValid = false;
				return Status::newFatal( 'nosuchusershort', $this->target );
			}
			$conds[] = $targetExpr;
		}

		$hideIP = in_array( 'addressblocks', $this->options );
		$hideRange = in_array( 'rangeblocks', $this->options );
		$hideUser = in_array( 'userblocks', $this->options );

		$dbr = $this->globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		if ( $hideIP && $hideRange && $hideUser ) {
			$this->queryValid = false;
		}
		if ( $hideIP ) {
			$conds[] = $dbr->makeList( [
				'gb_range_end > gb_range_start',
				$dbr->expr( 'gb_target_central_id', '!=', 0 ),
			], IReadableDatabase::LIST_OR );
		}
		if ( $hideRange ) {
			$conds[] = 'gb_range_end = gb_range_start';
		}
		if ( $hideUser ) {
			$conds['gb_target_central_id'] = 0;
		}

		$hideTemp = in_array( 'tempblocks', $this->options );
		$hideIndef = in_array( 'indefblocks', $this->options );
		if ( $hideTemp && $hideIndef ) {
			$this->queryValid = false;
		} elseif ( $hideTemp ) {
			$conds['gb_expiry'] = $dbr->getInfinity();
		} elseif ( $hideIndef ) {
			$conds[] = $dbr->expr( 'gb_expiry', '!=', $dbr->getInfinity() );
		}
		$this->conds = $conds;

		return true;
	}

	public function onSuccess() {
		// If the form data was valid, then use the WHERE conditions generated from it to get a page of results.
		$pager = new GlobalBlockListPager(
			$this->getContext(),
			$this->conds,
			$this->getLinkRenderer(),
			$this->commentFormatter,
			$this->lookup,
			$this->globalBlockingLinkBuilder,
			$this->globalBlockingConnectionProvider,
			$this->globalBlockLocalStatusLookup,
			$this->userIdentityLookup,
			$this->globalBlockingUserVisibilityLookup
		);

		$out = $this->getOutput();
		if ( $this->queryValid ) {
			$body = $pager->getBody();
			if ( $body != '' ) {
				$out->addHTML(
					$pager->getNavigationBar() .
					Html::rawElement( 'ul', [], $body ) .
					$pager->getNavigationBar()
				);
				return;
			}
		}

		$this->noResults();
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

	public function getShowAlways(): bool {
		return true;
	}

	protected function getDisplayFormat(): string {
		return 'ooui';
	}

	public function getDescription(): Message {
		$messageKey = 'globalblocking-list';
		if ( $this->getConfig()->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
			$messageKey .= '-new';
		}
		return $this->msg( $messageKey );
	}

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
