<?php

namespace MediaWiki\Extension\GlobalBlocking\Special;

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
use MediaWiki\Pager\ReverseChronologicalPager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\WikiMap\WikiMap;

class GlobalBlockListPager extends ReverseChronologicalPager {
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

		// Add a module used to provide styling to the reason for global blocks.
		$this->getOutput()->addModuleStyles( 'mediawiki.interface.helpers.styles' );
	}

	public function formatRow( $row ) {
		// Construct a list of block options that are relevant to the block in this $row.
		$options = [];

		// Check for the block being locally disabled, and if it is disabled then add that as an option.
		$wlinfo = $this->globalBlockLocalStatusLookup->getLocalWhitelistInfo( $row->gb_id );
		if ( $wlinfo ) {
			$options[] = $this->msg(
				'globalblocking-list-whitelisted',
				User::whois( $wlinfo['user'] ), $wlinfo['reason']
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

		// Get the performer of the block, along with the wiki they performed the block. If a user page link
		// can be generated, then it is added.
		$performerUsername = $this->lookup->nameFromCentralId( $row->gb_by_central_id ) ?? '';
		$performerWiki = WikiMap::getWikiName( $row->gb_by_wiki );
		$performerLink = $this->globalBlockingLinkBuilder->maybeLinkUserpage( $row->gb_by_wiki, $performerUsername );

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

		// Combine the options specified for the block and wrap them in parentheses. If no options are specified,
		// then just use empty text to avoid stray parentheses.
		$optionsAsText = '';
		if ( count( $options ) ) {
			$optionsAsText = $this->msg( 'parentheses', $this->getLanguage()->commaList( $options ) )->text();
		}

		$blockTimestamp = $this->getLinkRenderer()->makeKnownLink(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$this->getLanguage()->userTimeAndDate( $row->gb_timestamp, $this->getUser() ),
			[],
			[ 'target' => "#{$row->gb_id}" ],
		);

		$msg = $this->msg( 'globalblocking-list-item' )
			->rawParams( $blockTimestamp )
			->params( $performerUsername, $performerLink, $performerWiki, $targetName )
			->rawParams( $targetUserLink )
			->expiryParams( $row->gb_expiry )
			->params( $optionsAsText )
			->rawParams(
				$this->commentFormatter->formatBlock( $row->gb_reason ),
				$this->globalBlockingLinkBuilder->getActionLinks(
					$this->getAuthority(), $row->gb_address, $this->getContext()
				)
			)
			->parse();

		return Html::rawElement( 'li', [], $msg );
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
}
