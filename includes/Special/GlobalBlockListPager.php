<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

use InvalidArgumentException;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Pager\IndexPager;
use MediaWiki\Pager\TablePager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\WikiMap\WikiMap;
use stdClass;

class GlobalBlockListPager extends TablePager {
	private array $queryConds;

	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup;

	/**
	 * @param IContextSource $context
	 * @param array $conds
	 * @param LinkRenderer $linkRenderer
	 * @param CommentFormatter $commentFormatter
	 * @param CentralIdLookup $lookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 * @param GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	 */
	public function __construct(
		IContextSource $context,
		array $conds,
		LinkRenderer $linkRenderer,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	) {
		// Set database before parent constructor so that the DB that has the globalblocks table is used
		// over the local database which may not be the same database.
		$this->mDb = $globalBlockingConnectionProvider->getReplicaGlobalBlockingDatabase();
		parent::__construct( $context, $linkRenderer );
		$this->queryConds = $conds;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockingUserVisibilityLookup = $globalBlockingUserVisibilityLookup;

		$this->getOutput()->addModuleStyles( [ 'mediawiki.interface.helpers.styles', 'ext.globalBlocking.styles' ] );
		$this->mDefaultDirection = IndexPager::DIR_DESCENDING;
	}

	protected function getFieldNames() {
		return [
			'gb_timestamp' => $this->msg( 'globalblocking-list-table-heading-timestamp' )->text(),
			'target' => $this->msg( 'globalblocking-list-table-heading-target' )->text(),
			'gb_expiry' => $this->msg( 'globalblocking-list-table-heading-expiry' )->text(),
			'by' => $this->msg( 'globalblocking-list-table-heading-by' )->text(),
			'params' => $this->msg( 'globalblocking-list-table-heading-params' )->text(),
			'gb_reason' => $this->msg( 'globalblocking-list-table-heading-reason' )->text(),
		];
	}

	/**
	 * @param string $name
	 * @param string|null $value
	 * @return string
	 */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		switch ( $name ) {
			case 'gb_timestamp':
				// Link the timestamp to the block ID. This allows users without permissions to change blocks
				// to be able to generate a link to a specific block.
				return $this->getLinkRenderer()->makeKnownLink(
					SpecialPage::getTitleFor( 'GlobalBlockList' ),
					$this->getLanguage()->userTimeAndDate( $value, $this->getUser() ),
					[],
					[ 'target' => "#{$row->gb_id}" ],
				);
			case 'target':
				return $this->formatTarget( $row );
			case 'gb_expiry':
				$actionLinks = Html::rawElement(
					'span',
					[ 'class' => 'mw-globalblocking-globalblocklist-actions' ],
					$this->globalBlockingLinkBuilder->getActionLinks(
						$this->getAuthority(), $row->gb_address, $this->getContext()
					)
				);
				return $this->msg( 'globalblocking-list-table-cell-expiry' )
					->expiryParams( $value )
					->rawParams( $actionLinks )
					->parse();
			case 'by':
				// Get the performer of the block, along with the wiki they performed the block. If a user page link
				// can be generated, then it is added.
				$performerUsername = $this->lookup->nameFromCentralId( $row->gb_by_central_id ) ?? '';
				$performerWiki = WikiMap::getWikiName( $row->gb_by_wiki );
				$performerLink = $this->globalBlockingLinkBuilder->maybeLinkUserpage(
					$row->gb_by_wiki, $performerUsername
				);

				return $this->msg(
					'globalblocking-list-table-cell-by', $performerLink, $performerWiki
				)->parse();
			case 'gb_reason':
				return $this->commentFormatter->format( $value );
			case 'params':
				// Construct a list of block options that are relevant to the block in this $row.
				$options = [];

				$wlinfo = $this->globalBlockLocalStatusLookup->getLocalWhitelistInfo( $row->gb_id );
				if ( $wlinfo ) {
					$options[] = $this->msg(
						'globalblocking-list-whitelisted',
						$this->userIdentityLookup->getUserIdentityByUserId( $wlinfo['user'] ), $wlinfo['reason']
					)->text();
				}

				// If the block is set to target only anonymous users, then indicate this in the options list.
				if ( $row->gb_anon_only ) {
					$options[] = $this->msg( 'globalblocking-list-anononly' )->text();
				}

				// If the block is set to prevent account creation, then indicate this in the options list.
				if ( $row->gb_create_account ) {
					$options[] = $this->msg( 'globalblocking-block-flag-account-creation-disabled' )->text();
				}

				// Wrap the options in <li> HTML tags to make the options into a list.
				$options = array_map( static function ( $prop ) {
					return Html::rawElement( 'li', [], $prop );
				}, $options );

				return Html::rawElement( 'ul', [], implode( '', $options ) );
			default:
				throw new InvalidArgumentException( "Unable to format $name" );
		}
	}

	/**
	 * Format the target field
	 * @param stdClass $row
	 * @return string
	 */
	private function formatTarget( $row ) {
		// Get the target of the block from the database row. If the target is a user, then the code will determine
		// whether the username is hidden from the current authority.
		if ( $row->gb_target_central_id ) {
			// Get the target name using the CentralIdLookup if the target is a user. A raw lookup is done, as we
			// need to separately know if the user is hidden (as opposed to does not exist).
			// GlobalBlockingUserVisibility::checkAuthorityCanSeeUser method will appropriately hide the user.
			$targetName = $this->lookup->nameFromCentralId(
				$row->gb_target_central_id, CentralIdLookup::AUDIENCE_RAW
			) ?? '';
			$targetUserVisible = $this->globalBlockingUserVisibilityLookup
				->checkAuthorityCanSeeUser( $targetName, $this->getAuthority() );
			if ( !$targetUserVisible ) {
				$targetName = '';
			}
		} else {
			// If the target is an IP, then we can use the gb_address column and also can assume that the username
			// will always be visible.
			$targetName = $row->gb_address;
			$targetUserVisible = true;
		}

		// Generate the user link / tool links for the target unless the target is hidden from the current authority.
		if ( $targetUserVisible ) {
			// If the central ID refers to a valid name, then try to get the local ID of that user.
			$targetUserId = 0;
			if ( $targetName ) {
				$targetLocalUserIdentity = $this->userIdentityLookup->getUserIdentityByName( $targetName );
				if ( $targetLocalUserIdentity ) {
					$targetUserId = $targetLocalUserIdentity->getId();
				}
			}
			// Generate the user link and user tool links.
			$targetUserLink = Linker::userLink( $targetUserId, $targetName );
			if ( $targetName ) {
				$targetUserLink .= Linker::userToolLinks(
					$targetUserId, $targetName, true, Linker::TOOL_LINKS_NOBLOCK
				);
			}
		} else {
			$targetUserLink = Html::element(
				'span',
				[ 'class' => 'history-deleted' ],
				$this->msg( 'rev-deleted-user' )->text()
			);
		}

		return $targetUserLink;
	}

	public function getQueryInfo() {
		return [
			'tables' => 'globalblocks',
			'fields' => GlobalBlockLookup::selectFields(),
			'conds' => $this->queryConds,
		];
	}

	public function getIndexField() {
		return 'gb_timestamp';
	}

	protected function getTableClass() {
		return parent::getTableClass() . ' mw-globalblocking-globalblocklist';
	}

	public function getDefaultSort() {
		return '';
	}

	protected function isFieldSortable( $name ) {
		return false;
	}
}
