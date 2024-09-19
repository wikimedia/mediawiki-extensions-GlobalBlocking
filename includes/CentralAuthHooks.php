<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CentralAuth\Hooks\CentralAuthInfoFieldsHook;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\SpecialPage\SpecialPage;
use MessageLocalizer;

/**
 * Hook handlers for hooks provided by the CentralAuth extension.
 */
class CentralAuthHooks implements CentralAuthInfoFieldsHook {
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private LinkRenderer $linkRenderer;
	private MessageLocalizer $messageLocalizer;

	/**
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		LinkRenderer $linkRenderer
	) {
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->linkRenderer = $linkRenderer;
		$this->messageLocalizer = RequestContext::getMain();
	}

	/** @inheritDoc */
	public function onCentralAuthInfoFields(
		CentralAuthUser $centralAuthUser, IContextSource $context, array &$attribs
	) {
		$target = $centralAuthUser->getName();
		$relevantGlobalBlockId = $this->globalBlockLookup->getGlobalBlockId( $target );
		if ( $relevantGlobalBlockId ) {
			// If the user is globally blocked, then link the "yes" to Special:GlobalBlockList to be able to show
			// the global block that is applied to the user.
			$data = $this->linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlockList', $target ),
				$this->messageLocalizer->msg( 'centralauth-admin-yes' )->text()
			);
		} else {
			$data = $this->messageLocalizer->msg( 'centralauth-admin-no' )->escaped();
		}
		// Add action links after the "yes" or "no" to allow users to manage global blocks on the user.
		$globalBlockActionLinks = $this->globalBlockingLinkBuilder->getActionLinks(
			$context->getAuthority(), $target, $context, true
		);
		if ( $globalBlockActionLinks !== '' ) {
			$data .= $this->messageLocalizer->msg( 'word-separator' )->escaped() .
				$globalBlockActionLinks;
		}

		// If there's no blocks or actions, just don't display anything
		if ( !$relevantGlobalBlockId && $globalBlockActionLinks === '' ) {
			return;
		}

		$attribs['globalblock'] = [
			'label' => 'globalblocking-centralauth-admin-info-globalblock',
			'data' => $data,
		];
	}
}
