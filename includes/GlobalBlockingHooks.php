<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\Hook\GetUserBlockHook;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingConnectionProvider;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLocalStatusLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager;
use MediaWiki\Hook\ContributionsToolLinksHook;
use MediaWiki\Hook\GetBlockErrorMessageKeyHook;
use MediaWiki\Hook\GetLogTypesOnUserHook;
use MediaWiki\Hook\OtherBlockLogLinkHook;
use MediaWiki\Hook\SpecialContributionsBeforeMainOutputHook;
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\Hook\UserIsBlockedGloballyHook;
use MediaWiki\User\User;
use MediaWiki\User\UserNameUtils;
use Wikimedia\IPUtils;

/**
 * MediaWiki hook handlers for the GlobalBlocking extension
 *
 * @license GPL-2.0-or-later
 */
class GlobalBlockingHooks implements
	GetUserBlockHook,
	UserIsBlockedGloballyHook,
	GetBlockErrorMessageKeyHook,
	OtherBlockLogLinkHook,
	SpecialContributionsBeforeMainOutputHook,
	GetLogTypesOnUserHook,
	ContributionsToolLinksHook
{
	private Config $config;
	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private GlobalBlockingLinkBuilder $globalBlockLinkBuilder;
	private GlobalBlockLookup $globalBlockLookup;
	private GlobalBlockingConnectionProvider $globalBlockingConnectionProvider;
	private GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup;
	private UserNameUtils $userNameUtils;
	private GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup;

	/**
	 * @param Config $mainConfig
	 * @param CommentFormatter $commentFormatter
	 * @param CentralIdLookup $lookup
	 * @param GlobalBlockingLinkBuilder $globalBlockLinkBuilder
	 * @param GlobalBlockLookup $globalBlockLookup
	 * @param GlobalBlockingConnectionProvider $globalBlockingConnectionProvider
	 * @param GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup
	 * @param UserNameUtils $userNameUtils
	 * @param GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	 */
	public function __construct(
		Config $mainConfig,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		GlobalBlockingLinkBuilder $globalBlockLinkBuilder,
		GlobalBlockLookup $globalBlockLookup,
		GlobalBlockingConnectionProvider $globalBlockingConnectionProvider,
		GlobalBlockLocalStatusLookup $globalBlockLocalStatusLookup,
		UserNameUtils $userNameUtils,
		GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	) {
		$this->config = $mainConfig;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->globalBlockLinkBuilder = $globalBlockLinkBuilder;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->globalBlockingConnectionProvider = $globalBlockingConnectionProvider;
		$this->globalBlockLocalStatusLookup = $globalBlockLocalStatusLookup;
		$this->userNameUtils = $userNameUtils;
		$this->globalBlockingUserVisibilityLookup = $globalBlockingUserVisibilityLookup;
	}

	/**
	 * Add a global block. If there are any existing blocks, add
	 * the global block into a CompositeBlock.
	 *
	 * @param User $user
	 * @param string|null $ip null unless we're checking the session user
	 * @param AbstractBlock|null &$block
	 * @return bool
	 */
	public function onGetUserBlock( $user, $ip, &$block ) {
		if ( !$this->config->get( 'ApplyGlobalBlocks' ) ) {
			return true;
		}

		$globalBlock = $this->globalBlockLookup->getUserBlock( $user, $ip );
		if ( !$globalBlock ) {
			return true;
		}

		if ( !$block ) {
			$block = $globalBlock;
			return true;
		}

		// User is locally blocked and globally blocked. We need a CompositeBlock.
		$allBlocks = $block->toArray();
		$allBlocks[] = $globalBlock;
		$block = new CompositeBlock( [
			'address' => $ip ?? $user->getName(),
			'reason' => new Message( 'blockedtext-composite-reason' ),
			'originalBlocks' => $allBlocks,
		] );
		return true;
	}

	/**
	 * @param User $user
	 * @param string $ip
	 * @param bool &$blocked
	 * @param AbstractBlock|null &$block
	 *
	 * @return bool
	 */
	public function onUserIsBlockedGlobally( $user, $ip, &$blocked, &$block ) {
		$block = $this->globalBlockLookup->getUserBlock( $user, $ip );
		if ( $block !== null ) {
			$blocked = true;
			return false;
		}
		return true;
	}

	/**
	 * @param Block $block
	 * @param string &$key
	 *
	 * @return bool
	 */
	public function onGetBlockErrorMessageKey( Block $block, string &$key ) {
		if ( $block instanceof GlobalBlock ) {
			if ( $block->getXff() ) {
				$key = 'globalblocking-blockedtext-xff';
			} elseif ( IPUtils::isValid( $block->getTargetName() ) ) {
				$key = 'globalblocking-blockedtext-ip';
			} elseif ( IPUtils::isValidRange( $block->getTargetName() ) ) {
				$key = 'globalblocking-blockedtext-range';
			} else {
				$key = 'globalblocking-blockedtext-user';
			}
			return false;
		}
		return true;
	}

	/**
	 * Creates a link to the global block log
	 * @param array &$msg Message with a link to the global block log
	 * @param string $target The username or IP address to be checked
	 *
	 * @return bool true
	 */
	public function onOtherBlockLogLink( &$msg, $target ) {
		$authority = RequestContext::getMain()->getAuthority();
		// If the target is a username, then we need the central ID for this user to do the lookup.
		$centralId = 0;
		$ip = null;
		if ( IPUtils::isIPAddress( $target ) ) {
			$ip = $target;
		} elseif ( !$this->config->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
			// If global account blocks are disabled, we can ignore checking for global blocks on accounts here.
			return true;
		} elseif ( !$this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser( $target, $authority ) ) {
			// If the current user cannot see the target, then we should not show the global block link even if
			// a global block exists for this user.
			return true;
		} else {
			$centralId = $this->lookup->centralIdFromName( $target, $authority );
		}

		// Check to see if the target is globally blocked, skipping the local disable check as we're only interested
		// if a global block exists for this target (as opposed to whether it actually is applied for this user).
		$block = $this->globalBlockLookup->getGlobalBlockingBlock(
			$ip, $centralId, GlobalBlockLookup::SKIP_LOCAL_DISABLE_CHECK
		);
		if ( $block ) {
			// If the target is globally blocked, then add a link to the global block list for this target.
			$msg[] = Html::rawElement(
				'span',
				[ 'class' => 'mw-globalblock-loglink plainlinks' ],
				wfMessage( 'globalblocking-loglink', $target )->parse()
			);
		}

		return true;
	}

	/**
	 * Show global block notice on Special:Contributions.
	 * @param int $userId
	 * @param User $user
	 * @param SpecialPage $sp
	 *
	 * @return bool
	 */
	public function onSpecialContributionsBeforeMainOutput( $userId, $user, $sp ) {
		$name = $user->getName();

		if ( IPUtils::isIPAddress( $name ) ) {
			$ip = $name;
			$centralId = 0;
		} elseif ( !$this->config->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
			// If global account blocks are disabled, we can ignore checking for global blocks on accounts here.
			return true;
		} elseif ( !$this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser(
			$name, $sp->getAuthority()
		) ) {
			// If the current user cannot see the target, then we should not show the global block log entry.
			return true;
		} else {
			$ip = null;
			$centralId = $this->lookup->centralIdFromName( $name, $sp->getAuthority() );
		}
		$block = $this->globalBlockLookup->getGlobalBlockingBlock( $ip, $centralId );

		if ( $block ) {
			$pager = new GlobalBlockListPager(
				$sp->getContext(),
				// Unused as we're not actually querying the database using the GlobalBlockListPager
				// because the query was made in GlobalBlockLookup::getGlobalBlockingBlock.
				[],
				$sp->getLinkRenderer(),
				$this->commentFormatter,
				$this->lookup,
				$this->globalBlockLinkBuilder,
				$this->globalBlockingConnectionProvider,
				$this->globalBlockLocalStatusLookup
			);
			$body = $pager->formatRow( $block );

			$out = $sp->getOutput();
			$out->addHTML(
				Html::warningBox(
					$sp->msg( 'globalblocking-contribs-notice', $name )->parseAsBlock() .
					Html::rawElement(
						'ul',
						[ 'class' => 'mw-logevent-loglines' ],
						$body
					),
					'mw-warning-with-logexcerpt'
				)
			);
		}

		return true;
	}

	/**
	 * Adds a link on Special:Contributions to Special:GlobalBlock for privileged users.
	 *
	 * @param int $id User ID
	 * @param Title $title User page title
	 * @param array &$tools Tool links
	 * @param SpecialPage $specialPage SpecialPage instance for context and services.
	 * @return bool|void
	 */
	public function onContributionsToolLinks( $id, Title $title, array &$tools, SpecialPage $specialPage ) {
		if ( !$specialPage->getAuthority()->isAllowed( 'globalblock' ) ) {
			// Return early if the user does not have the globalblock right as there will be no relevant tool links
			// to add to the contributions page.
			return;
		}

		// Normalise the target to ensure that the call to GlobalBlockLookup::getGlobalBlockId
		// works as intended (as it expects a normalised target).
		$target = $title->getText();
		if ( IPUtils::isValidRange( $target ) ) {
			$target = IPUtils::sanitizeRange( $target );
		} elseif ( IPUtils::isIPAddress( $target ) ) {
			$target = IPUtils::sanitizeIP( $target );
		} elseif ( !$this->config->get( 'GlobalBlockingAllowGlobalAccountBlocks' ) ) {
			// If global account blocks are disabled, we should not add links to the relevant special pages as they
			// will not support global blocks on accounts.
			return;
		} elseif ( !$this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser(
			$target, $specialPage->getAuthority()
		) ) {
			// If the current user cannot see the target, then we should not show any contribution tool links
			// to avoid leaking that a user exists with this username and whether this hidden user is globally
			// blocked.
			return;
		} else {
			$target = $this->userNameUtils->getCanonical( $target );
		}

		if ( !$target ) {
			// If the target is invalid, then we will have no links to show and should return early.
			return;
		}

		$linkRenderer = $specialPage->getLinkRenderer();
		if ( $this->globalBlockLookup->getGlobalBlockId( $target ) === 0 ) {
			$tools['globalblock'] = $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlock', $target ),
				$specialPage->msg( 'globalblocking-contribs-block' )->text()
			);
		} else {
			$tools['globalblock'] = $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'GlobalBlock', $target ),
				$specialPage->msg( 'globalblocking-contribs-modify' )->text()
			);

			$tools['globalunblock'] = $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'RemoveGlobalBlock', $target ),
				$specialPage->msg( 'globalblocking-contribs-remove' )->text()
			);
		}
	}

	/**
	 * So users can just type in a username for target and it'll work
	 * @param array &$types
	 * @return bool
	 */
	public function onGetLogTypesOnUser( &$types ) {
		$types[] = 'gblblock';

		return true;
	}
}
