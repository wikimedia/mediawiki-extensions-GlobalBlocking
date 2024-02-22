<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;

/**
 * A service that builds links to other global blocking special pages and also
 * builds user links to user pages on other wikis.
 *
 * @since 1.42
 */
class GlobalBlockingLinkBuilder {
	/**
	 * Build links to other global blocking special pages. These are for use in the subtitle of
	 * GlobalBlocking extension special pages.
	 *
	 * @param SpecialPage $sp SpecialPage instance for context
	 * @return string links to special pages
	 */
	public function buildSubtitleLinks( SpecialPage $sp ): string {
		// Add a few useful links
		$links = [];
		$pagetype = $sp->getName();
		$linkRenderer = $sp->getLinkRenderer();

		// Don't show a link to a special page on the special page itself.
		// Show the links only if the user has sufficient rights
		if ( $pagetype !== 'GlobalBlockList' ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockList' );
			$links[] = $linkRenderer->makeKnownLink( $title, $sp->msg( 'globalblocklist' )->text() );
		}
		$canBlock = $sp->getAuthority()->isAllowed( 'globalblock' );
		if ( $pagetype !== 'GlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlock' );
			$links[] = $linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-block' )->text() );
		}
		if ( $pagetype !== 'RemoveGlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'RemoveGlobalBlock' );
			$links[] = $linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-unblock' )->text() );
		}
		if ( $pagetype !== 'GlobalBlockStatus' && $sp->getAuthority()->isAllowed( 'globalblock-whitelist' ) ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockStatus' );
			$links[] = $linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-status' )->text() );
		}
		if ( $pagetype === 'GlobalBlock' && $sp->getAuthority()->isAllowed( 'editinterface' ) ) {
			$title = Title::makeTitle( NS_MEDIAWIKI, 'Globalblocking-block-reason-dropdown' );
			$links[] = $linkRenderer->makeKnownLink(
				$title,
				$sp->msg( 'globalblocking-block-edit-dropdown' )->text(),
				[],
				[ 'action' => 'edit' ]
			);
		}
		if ( count( $links ) ) {
			return $sp->msg( 'parentheses' )
				->rawParams( $sp->getLanguage()->pipeList( $links ) )
				->escaped();
		}
		return '';
	}

	/**
	 * If possible, build a link to the user page of the given user on the given wiki.
	 *
	 * @param string $wikiID
	 * @param string $user
	 * @return string Wikitext which may contain a external link to the user page on the given wiki.
	 */
	public function maybeLinkUserpage( string $wikiID, string $user ): string {
		$wiki = WikiMap::getWiki( $wikiID );

		if ( $wiki ) {
			return "[" . $wiki->getFullUrl( "User:$user" ) . " $user]";
		}
		return $user;
	}
}
