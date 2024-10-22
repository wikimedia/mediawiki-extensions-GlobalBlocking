<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\WikiMap\WikiMap;
use stdClass;

/**
 * A service that allows callers to generate snippets of HTML for a given globalblocks row. Used to de-duplicate
 * code between {@link GlobalBlockListPager} and {@link GlobalBlockingHooks}.
 *
 * @since 1.43
 */
class GlobalBlockingGlobalBlockDetailsRenderer {
	private CentralIdLookup $centralIdLookup;
	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	public function __construct(
		CentralIdLookup $centralIdLookup,
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		$this->centralIdLookup = $centralIdLookup;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockingUserVisibilityLookup = $globalBlockingUserVisibilityLookup;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
	}

	/**
	 * Gets a formatted target for a given globalblocks row. Intended for use when displaying the global block to
	 * a user in a page such as Special:GlobalBlockList or Special:Contributions.
	 *
	 * @param stdClass $row The globalblocks database row
	 * @param IContextSource $context Context to use for message generation and authority.
	 * @return string The formatted target HTML
	 */
	public function formatTargetForDisplay( stdClass $row, IContextSource $context ): string {
		// Return early if the global block is an autoblock, as we should just use the ID to reference the block target
		// to avoid exposing the IP addres that was globally autoblocked.
		if ( $row->gb_autoblock_parent_id ) {
			return $context->msg( 'globalblocking-global-autoblock-id', $row->gb_id )->parse();
		}

		[ $targetName, $targetUserVisible ] = $this->getTargetUsername( $row, $context );

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
				$context->msg( 'rev-deleted-user' )->text()
			);
		}

		return $targetUserLink;
	}

	/**
	 * Gets the target username for a given global block, along with whether that username is hidden from the
	 * current authority.
	 *
	 * @param stdClass $row The globalblocks database row
	 * @param IContextSource $context Context to use for message generation and authority.
	 * @return array The target username suitable for use as the first item, and whether the target is visible to the
	 *   current user as the second item. The target username will be an empty string if the user cannot view it.
	 */
	public function getTargetUsername( stdClass $row, IContextSource $context ): array {
		// The target username is always hidden if it is an autoblock.
		if ( $row->gb_autoblock_parent_id ) {
			return [ '', false ];
		}

		// Get the target of the block from the database row. If the target is a user, then the code will determine
		// whether the username is hidden from the current authority.
		if ( $row->gb_target_central_id ) {
			// Get the target name using the CentralIdLookup if the target is a user. A raw lookup is done, as we
			// need to separately know if the user is hidden (as opposed to does not exist).
			// GlobalBlockingUserVisibility::checkAuthorityCanSeeUser method will appropriately hide the user.
			$targetName = $this->centralIdLookup->nameFromCentralId(
				$row->gb_target_central_id, CentralIdLookup::AUDIENCE_RAW
			) ?? '';
			$targetUserVisible = $this->globalBlockingUserVisibilityLookup
				->checkAuthorityCanSeeUser( $targetName, $context->getAuthority() );
			if ( !$targetUserVisible ) {
				$targetName = '';
			}
		} else {
			// If the target is an IP, then we can use the gb_address column and also can assume that the username
			// will always be visible.
			$targetName = $row->gb_address;
			$targetUserVisible = true;
		}

		return [ $targetName, $targetUserVisible ];
	}

	/**
	 * Gets a list of formatted block options for display for a specific global block.
	 *
	 * @param stdClass $row The globalblocks database row
	 * @param IContextSource $context Context to use for message generation and authority.
	 * @return array An array of formatted block options
	 */
	public function getBlockOptionsForDisplay( stdClass $row, IContextSource $context ): array {
		// Construct a list of block options that are relevant to the block in this $row.
		$options = [];

		$wlinfo = $this->globalBlockLocalStatusLookup->getLocalStatusInfo( $row->gb_id );
		if ( $wlinfo ) {
			$options[] = $context->msg(
				'globalblocking-list-whitelisted',
				$this->userIdentityLookup->getUserIdentityByUserId( $wlinfo['user'] ), $wlinfo['reason']
			)->text();
		}

		// If the block is set to target only anonymous users, then indicate this in the options list.
		if ( $row->gb_anon_only ) {
			$options[] = $context->msg( 'globalblocking-list-anononly' )->text();
		}

		// If the block is set to prevent account creation, then indicate this in the options list.
		if ( $row->gb_create_account ) {
			$options[] = $context->msg( 'globalblocking-block-flag-account-creation-disabled' )->text();
		}

		return $options;
	}

	/**
	 * Gets the the HTML to display the performer of a given global block
	 *
	 * @param stdClass $row The globalblocks database row
	 * @param IContextSource $context Context to use for message generation and authority.
	 * @return string The HTML for the performer of the global block
	 */
	public function getPerformerForDisplay( stdClass $row, IContextSource $context ): string {
		// Get the performer of the block, along with the wiki they performed the block. If a user page link
		// can be generated, then it is added.
		$performerUsername = $this->centralIdLookup->nameFromCentralId( $row->gb_by_central_id ) ?? '';
		$performerWiki = WikiMap::getWikiName( $row->gb_by_wiki );
		$performerLink = $this->globalBlockingLinkBuilder->maybeLinkUserpage(
			$row->gb_by_wiki, $performerUsername, $context->getTitle()
		);

		return $context->msg( 'globalblocking-list-table-cell-by' )
			->rawParams( $performerLink )
			->params( $performerWiki )
			->parse();
	}
}
