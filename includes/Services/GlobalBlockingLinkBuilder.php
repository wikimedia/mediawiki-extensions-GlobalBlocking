<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use HtmlArmor;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\IContextSource;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Permissions\Authority;
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

	public const CONSTRUCTOR_OPTIONS = [
		'ApplyGlobalBlocks',
		'GlobalBlockingCentralWiki',
	];

	private ServiceOptions $options;
	private LinkRenderer $linkRenderer;
	private GlobalBlockLookup $globalBlockLookup;

	public function __construct(
		ServiceOptions $options,
		LinkRenderer $linkRenderer,
		GlobalBlockLookup $globalBlockLookup
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->linkRenderer = $linkRenderer;
		$this->globalBlockLookup = $globalBlockLookup;
	}

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

		// Don't show a link to a special page on the special page itself.
		// Show the links only if the user has sufficient rights
		if ( $pagetype !== 'GlobalBlockList' ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockList' );
			$links[] = $this->linkRenderer->makeKnownLink( $title, $sp->msg( 'globalblocklist' )->text() );
		}
		$canBlock = $sp->getAuthority()->isAllowed( 'globalblock' );
		if ( $pagetype !== 'GlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlock' );
			$links[] = $this->linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-block' )->text()
			);
		}
		if ( $pagetype !== 'RemoveGlobalBlock' && $canBlock ) {
			$title = SpecialPage::getTitleFor( 'RemoveGlobalBlock' );
			$links[] = $this->linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-unblock' )->text() );
		}
		if ( $pagetype !== 'GlobalBlockStatus' && $sp->getAuthority()->isAllowed( 'globalblock-whitelist' ) ) {
			$title = SpecialPage::getTitleFor( 'GlobalBlockStatus' );
			$links[] = $this->linkRenderer->makeKnownLink(
				$title, $sp->msg( 'globalblocking-goto-status' )->text() );
		}
		if ( $pagetype === 'GlobalBlock' && $sp->getAuthority()->isAllowed( 'editinterface' ) ) {
			$title = Title::makeTitle( NS_MEDIAWIKI, 'Globalblocking-block-reason-dropdown' );
			$links[] = $this->linkRenderer->makeKnownLink(
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
	 * @param LinkTarget $currentPage
	 * @return string HTML which is the link to the user page, or plaintext if no URL could be generated.
	 */
	public function maybeLinkUserpage( string $wikiID, string $user, LinkTarget $currentPage ): string {
		$wiki = WikiMap::getWiki( $wikiID );

		if ( $wiki ) {
			return $this->linkRenderer->makeExternalLink( $wiki->getFullUrl( "User:$user" ), $user, $currentPage );
		}
		return $user;
	}

	/**
	 * Get a link for the central wiki for the given special page.
	 * This is protected to allow mocking in tests.
	 *
	 * @param string $globalBlockingCentralWiki The wiki ID of the central wiki
	 * @param string $specialPageName The localised name of the special page (or in English as appropriate)
	 * @return string|false
	 */
	protected function getForeignURL( string $globalBlockingCentralWiki, string $specialPageName ) {
		return WikiMap::getForeignURL( $globalBlockingCentralWiki, $specialPageName );
	}

	/**
	 * Attempt to generate a link to the global blocking central wiki for the given special page.
	 *
	 * @param string $specialPageName The name of the special page, which will be localised
	 * @return string|null The URL to the central wiki, or null if no URL could be generated
	 */
	private function getCentralUrl( string $specialPageName ): ?string {
		$globalBlockingCentralWiki = $this->options->get( 'GlobalBlockingCentralWiki' );
		if ( $globalBlockingCentralWiki === false ) {
			return null;
		}
		$centralGlobalBlockingUrl = $this->getForeignURL(
			$globalBlockingCentralWiki,
			Title::makeName( NS_SPECIAL, $specialPageName, '', '', true )
		);
		if ( $centralGlobalBlockingUrl === false ) {
			return null;
		}
		return $centralGlobalBlockingUrl;
	}

	/**
	 * Generate a link to a given special page name which where possible should be on the central wiki.
	 *
	 * @param string $specialPageName The name of the special page, which will be localised
	 * @param string|HtmlArmor $linkText The display text for the link
	 * @param LinkTarget $currentPage The current page, to be passed to LinkRenderer::makeExternalLink
	 * @param array $queryParameters The query parameters for the link. optional.
	 * @return string The HTML of the generated link
	 */
	public function getLinkToCentralWikiSpecialPage(
		string $specialPageName, $linkText, LinkTarget $currentPage, array $queryParameters = []
	): string {
		$centralWikiLogUrl = $this->getCentralUrl( $specialPageName );
		if ( $centralWikiLogUrl ) {
			$centralWikiLogUrl = wfAppendQuery( $centralWikiLogUrl, $queryParameters );
			return $this->linkRenderer->makeExternalLink( $centralWikiLogUrl, $linkText, $currentPage );
		} else {
			return $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( $specialPageName ), $linkText, [], $queryParameters
			);
		}
	}

	/**
	 * Get action links to be displayed after the log entry or block list entry.
	 *
	 * @param Authority $authority The authority object for the user
	 * @param string $target The target of the block for the given log entry / block list entry.
	 * @param IContextSource $context The context to use to decide the language and format the
	 *   action links.
	 * @param bool $checkBlockStatus Whether to actually check if the given target is blocked.
	 *   If false, then assume that the user is blocked. Used to avoid repeated lookups for block
	 *   status on Special:Log.
	 * @return string The action links to be displayed
	 */
	public function getActionLinks(
		Authority $authority, string $target, IContextSource $context, bool $checkBlockStatus = false
	): string {
		$links = [];
		$canBlock = $authority->isAllowed( 'globalblock' );
		if ( $checkBlockStatus ) {
			$targetIsBlocked = $this->globalBlockLookup->getGlobalBlockId( $target );
		} else {
			$targetIsBlocked = true;
		}

		if ( $canBlock && $targetIsBlocked ) {
			$links[] = $this->getLinkToCentralWikiSpecialPage(
				'RemoveGlobalBlock',
				new HtmlArmor( $context->msg( 'globalblocking-list-unblock' )->parse() ),
				$context->getTitle(), [ 'address' => $target ]
			);
		}

		if (
			$this->options->get( 'ApplyGlobalBlocks' ) &&
			$authority->isAllowed( 'globalblock-whitelist' ) &&
			$targetIsBlocked
		) {
			$links[] = $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlockStatus' ),
				new HtmlArmor( $context->msg( 'globalblocking-list-whitelist' )->parse() ),
				[],
				[ 'address' => $target ]
			);
		}

		// Special:GlobalBlock does not support global block IDs, so don't display the link if the target is a
		// global block ID. This also hides the link for global autoblocks (which are only ever referenced by
		// their global block ID), which is good because we don't support modifying global autoblocks at all.
		if ( $canBlock && !GlobalBlockLookup::isAGlobalBlockId( $target ) ) {
			$globalBlockLinkMessage = $targetIsBlocked ? 'globalblocking-list-modify' : 'globalblocking-list-block';
			$links[] = $this->getLinkToCentralWikiSpecialPage(
				'GlobalBlock',
				new HtmlArmor( $context->msg( $globalBlockLinkMessage )->parse() ),
				$context->getTitle(), [ 'wpAddress' => $target ]
			);
		}

		if ( count( $links ) ) {
			return $context->msg( 'parentheses' )
				->rawParams( $context->getLanguage()->pipeList( $links ) )
				->escaped();
		}
		return '';
	}
}
