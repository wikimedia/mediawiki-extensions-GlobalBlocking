<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use HtmlArmor;
use Language;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Permissions\Authority;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\WikiMap\WikiMap;
use MessageLocalizer;

/**
 * A service that builds links to other global blocking special pages and also
 * builds user links to user pages on other wikis.
 *
 * @since 1.42
 */
class GlobalBlockingLinkBuilder {

	public const CONSTRUCTOR_OPTIONS = [
		'GlobalBlockingAllowGlobalAccountBlocks',
		'ApplyGlobalBlocks',
	];

	private ServiceOptions $options;
	private LinkRenderer $linkRenderer;
	private MessageLocalizer $messageLocalizer;
	private Language $language;

	public function __construct(
		ServiceOptions $options,
		LinkRenderer $linkRenderer,
		MessageLocalizer $messageLocalizer,
		Language $language
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->linkRenderer = $linkRenderer;
		$this->messageLocalizer = $messageLocalizer;
		$this->language = $language;
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
			$messageKey = $this->options->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ?
				'globalblocking-goto-block-new' : 'globalblocking-goto-block';
			$links[] = $this->linkRenderer->makeKnownLink( $title, $sp->msg( $messageKey )->text() );
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
	 * @return string Wikitext which may contain a external link to the user page on the given wiki.
	 */
	public function maybeLinkUserpage( string $wikiID, string $user ): string {
		$wiki = WikiMap::getWiki( $wikiID );

		if ( $wiki ) {
			return "[" . $wiki->getFullUrl( "User:$user" ) . " $user]";
		}
		return $user;
	}

	/**
	 * Get action links to be displayed after the log entry or block list entry.
	 *
	 * @param Authority $authority The authority object for the user
	 * @param string $target The target of the block for the given log entry / block list entry.
	 *
	 * @return string The action links to be displayed
	 */
	public function getActionLinks( Authority $authority, string $target ): string {
		$links = [];
		$canBlock = $authority->isAllowed( 'globalblock' );

		if ( $canBlock ) {
			$links[] = $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'RemoveGlobalBlock' ),
				new HtmlArmor( $this->messageLocalizer->msg( 'globalblocking-list-unblock' )->parse() ),
				[],
				[ 'address' => $target ]
			);
		}

		if ( $this->options->get( 'ApplyGlobalBlocks' ) && $authority->isAllowed( 'globalblock-whitelist' ) ) {
			$links[] = $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlockStatus' ),
				new HtmlArmor( $this->messageLocalizer->msg( 'globalblocking-list-whitelist' )->parse() ),
				[],
				[ 'address' => $target ]
			);
		}

		if ( $canBlock ) {
			$links[] = $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlock' ),
				new HtmlArmor( $this->messageLocalizer->msg( 'globalblocking-list-modify' )->parse() ),
				[],
				[ 'wpAddress' => $target ]
			);
		}

		if ( count( $links ) ) {
			return $this->messageLocalizer->msg( 'parentheses' )
				->rawParams( $this->language->pipeList( $links ) )
				->escaped();
		}
		return '';
	}
}
