<?php

namespace MediaWiki\Extension\GlobalBlocking;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\Hook\GetUserBlockHook;
use MediaWiki\Block\Hook\SpreadAnyEditBlockHook;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingGlobalBlockDetailsRenderer;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockManager;
use MediaWiki\Hook\ContributionsToolLinksHook;
use MediaWiki\Hook\GetBlockErrorMessageKeyHook;
use MediaWiki\Hook\GetLogTypesOnUserHook;
use MediaWiki\Hook\OtherBlockLogLinkHook;
use MediaWiki\Hook\SpecialContributionsBeforeMainOutputHook;
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\ContributionsSpecialPage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\Hook\UserIsBlockedGloballyHook;
use MediaWiki\User\User;
use MediaWiki\User\UserNameUtils;
use stdClass;
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
	ContributionsToolLinksHook,
	SpreadAnyEditBlockHook
{
	private Config $config;
	private CommentFormatter $commentFormatter;
	private CentralIdLookup $lookup;
	private GlobalBlockingLinkBuilder $globalBlockLinkBuilder;
	private GlobalBlockLookup $globalBlockLookup;
	private UserNameUtils $userNameUtils;
	private GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup;
	private GlobalBlockManager $globalBlockManager;
	private GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	public function __construct(
		Config $mainConfig,
		CommentFormatter $commentFormatter,
		CentralIdLookup $lookup,
		GlobalBlockingLinkBuilder $globalBlockLinkBuilder,
		GlobalBlockLookup $globalBlockLookup,
		UserNameUtils $userNameUtils,
		GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup,
		GlobalBlockManager $globalBlockManager,
		GlobalBlockingGlobalBlockDetailsRenderer $globalBlockDetailsRenderer,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		$this->config = $mainConfig;
		$this->commentFormatter = $commentFormatter;
		$this->lookup = $lookup;
		$this->globalBlockLinkBuilder = $globalBlockLinkBuilder;
		$this->globalBlockLookup = $globalBlockLookup;
		$this->userNameUtils = $userNameUtils;
		$this->globalBlockingUserVisibilityLookup = $globalBlockingUserVisibilityLookup;
		$this->globalBlockManager = $globalBlockManager;
		$this->globalBlockDetailsRenderer = $globalBlockDetailsRenderer;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
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
			if ( $block->getType() === Block::TYPE_AUTO ) {
				$key = 'globalblocking-blockedtext-autoblock';
				if ( $block->getXff() ) {
					// Generates globalblocking-blockedtext-autoblock-xff
					$key .= '-xff';
				}
			} elseif ( $block->getXff() ) {
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
		} elseif ( !$this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser( $target, $authority ) ) {
			// If the current user cannot see the target, then we should not show the global block link even if
			// a global block exists for this user.
			return true;
		} else {
			$centralId = $this->lookup->centralIdFromName( $target, $authority );
		}

		// Check to see if the target is globally blocked, skipping the local disable check as we're only interested
		// if a global block exists for this target (as opposed to whether it actually is applied for this user).
		// Also exclude global autoblocks so we don't reveal the IP address being autoblocked.
		$block = $this->globalBlockLookup->getGlobalBlockingBlock(
			$ip, $centralId, GlobalBlockLookup::SKIP_LOCAL_DISABLE_CHECK | GlobalBlockLookup::SKIP_AUTOBLOCKS
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
	 * @param ContributionsSpecialPage $sp
	 *
	 * @return bool
	 */
	public function onSpecialContributionsBeforeMainOutput( $userId, $user, $sp ) {
		$name = $user->getName();

		if ( !$sp->shouldShowBlockLogExtract( $user ) ) {
			return true;
		}

		if ( IPUtils::isIPAddress( $name ) ) {
			$ip = $name;
			$centralId = 0;
		} elseif ( !$this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser(
			$name, $sp->getAuthority()
		) ) {
			// If the current user cannot see the target, then we should not show the global block log entry.
			return true;
		} else {
			$ip = null;
			$centralId = $this->lookup->centralIdFromName( $name, $sp->getAuthority() );
		}
		// Always skip autoblocks, otherwise we would leak the IP address target of global autoblocks which is
		// private data.
		$block = $this->globalBlockLookup->getGlobalBlockingBlock(
			$ip, $centralId, GlobalBlockLookup::SKIP_AUTOBLOCKS
		);

		if ( $block ) {
			// Add the active global block to a warning box that is displayed at the top of Special:Contributions.
			$blockNoticeHtml = $sp->msg( 'globalblocking-contribs-notice', $name )->parseAsBlock();
			$blockNoticeHtml .= Html::rawElement(
				'ul',
				[ 'class' => 'mw-logevent-loglines' ],
				$this->getMockLogLineFromActiveGlobalBlock( $block, $sp )
			);

			// Add a 'View full logs' link that goes to the global block log on the central wiki, or the local wiki
			// if no central wiki is defined.
			$blockNoticeHtml .= $this->globalBlockLinkBuilder->getLinkToCentralWikiSpecialPage(
				'Log', $sp->msg( 'log-fulllog' )->text(), $sp->getFullTitle(),
				[ 'type' => 'gblblock', 'page' => $block->gb_address ]
			);

			$out = $sp->getOutput();
			$out->addHTML( Html::warningBox( $blockNoticeHtml, 'mw-warning-with-logexcerpt' ) );
		}

		return true;
	}

	private function getMockLogLineFromActiveGlobalBlock( stdClass $block, SpecialPage $sp ): string {
		$context = $sp->getContext();

		// Get the performer of the block.
		$performerUsername = $this->lookup->nameFromCentralId( $block->gb_by_central_id ) ?? '';
		$performerUserLink = $this->globalBlockDetailsRenderer->getPerformerForDisplay( $block, $context );

		// Combine the options specified for the block and wrap them in parentheses. If no options are specified,
		// then just use empty text to avoid stray parentheses.
		$options = $this->globalBlockDetailsRenderer->getBlockOptionsForDisplay( $block, $context );
		$optionsAsText = '';
		if ( count( $options ) ) {
			$optionsAsText = $context->msg( 'parentheses', $context->getLanguage()->commaList( $options ) )->text();
		}

		$blockTimestamp = $sp->getLinkRenderer()->makeKnownLink(
			SpecialPage::getTitleFor( 'GlobalBlockList' ),
			$context->getLanguage()->userTimeAndDate( $block->gb_timestamp, $context->getUser() ),
			[],
			[ 'target' => "#$block->gb_id" ],
		);

		[ $targetName ] = $this->globalBlockDetailsRenderer->getTargetUsername( $block, $context );

		$msg = $context->msg( 'globalblocking-contribs-mock-log-line' )
			->rawParams( $blockTimestamp )
			->params( $performerUsername )
			->rawParams( $performerUserLink )
			->params( $targetName )
			->rawParams( $this->globalBlockDetailsRenderer->formatTargetForDisplay( $block, $context ) )
			->expiryParams( $block->gb_expiry )
			->params( $optionsAsText )
			->rawParams(
				$this->commentFormatter->formatBlock( $block->gb_reason ),
				$this->globalBlockingLinkBuilder->getActionLinks( $context->getAuthority(), $targetName, $context )
			)
			->parse();

		return Html::rawElement( 'li', [], $msg );
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
		// Normalise the target to ensure that the call to GlobalBlockLookup::getGlobalBlockId
		// works as intended (as it expects a normalised target).
		$target = $title->getText();
		if ( IPUtils::isValidRange( $target ) ) {
			$target = IPUtils::sanitizeRange( $target );
		} elseif ( IPUtils::isIPAddress( $target ) ) {
			$target = IPUtils::sanitizeIP( $target );
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
		if ( $specialPage->getAuthority()->isAllowed( 'globalblock' ) ) {
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

		$tools['globalblocklog'] = $this->globalBlockLinkBuilder->getLinkToCentralWikiSpecialPage(
			'Log', $specialPage->msg( 'globalblocking-contribs-log' )->text(),
			$specialPage->getFullTitle(), [ 'type' => 'gblblock', 'page' => $target ]
		);
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

	/** @inheritDoc */
	public function onSpreadAnyEditBlock( $user, bool &$blockWasSpread ) {
		// Check if the local $user is globally blocked and that the global block enables autoblocks, returning
		// that no blocks were spread if both are not the case.
		// The IP is not specified because ::getUserBlock can only return one global block and we always want a
		// user block. An IP block may be selected by ::getUserBlock if it disables account creation but
		// the user block does not.
		$globalBlock = $this->globalBlockLookup->getUserBlock( $user, null );
		if ( !$globalBlock || !$globalBlock->isAutoblocking() ) {
			return;
		}

		// Actually perform the autoblock for the user's current IP address.
		$autoblockStatus = $this->globalBlockManager->autoblock( $globalBlock->getId(), $user->getRequest()->getIP() );

		// Indicate to the caller that a block was spread if a global autoblock was actually performed.
		$wasGlobalAutoBlockCreated = $autoblockStatus->isGood() && ( $autoblockStatus->getValue()['id'] ?? 0 );
		if ( $wasGlobalAutoBlockCreated ) {
			$blockWasSpread = true;
		}
	}
}
