<?php

namespace MediaWiki\Extension\GlobalBlocking;

use Config;
use Html;
use LogicException;
use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\Hook\GetUserBlockHook;
use MediaWiki\CommentFormatter\CommentFormatter;
use MediaWiki\Extension\GlobalBlocking\Special\GlobalBlockListPager;
use MediaWiki\Hook\ContributionsToolLinksHook;
use MediaWiki\Hook\GetBlockErrorMessageKeyHook;
use MediaWiki\Hook\GetLogTypesOnUserHook;
use MediaWiki\Hook\OtherBlockLogLinkHook;
use MediaWiki\Hook\SpecialContributionsBeforeMainOutputHook;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\User\Hook\SpecialPasswordResetOnSubmitHook;
use MediaWiki\User\Hook\UserIsBlockedGloballyHook;
use Message;
use RequestContext;
use SpecialPage;
use User;
use Wikimedia\IPUtils;

/**
 * MediaWiki hook handlers for the GlobalBlocking extension
 *
 * @license GPL-2.0-or-later
 */
class GlobalBlockingHooks implements
	GetUserBlockHook,
	UserIsBlockedGloballyHook,
	SpecialPasswordResetOnSubmitHook,
	GetBlockErrorMessageKeyHook,
	OtherBlockLogLinkHook,
	SpecialContributionsBeforeMainOutputHook,
	GetLogTypesOnUserHook,
	ContributionsToolLinksHook
{
	/** @var PermissionManager */
	private $permissionManager;

	/** @var Config */
	private $config;

	/** @var CommentFormatter */
	private $commentFormatter;

	/**
	 * @param PermissionManager $permissionManager
	 * @param Config $mainConfig
	 * @param CommentFormatter $commentFormatter
	 */
	public function __construct(
		PermissionManager $permissionManager,
		Config $mainConfig,
		CommentFormatter $commentFormatter
	) {
		$this->permissionManager = $permissionManager;
		$this->config = $mainConfig;
		$this->commentFormatter = $commentFormatter;
	}

	/**
	 * Extension registration callback
	 */
	public static function onRegistration() {
		global $wgGlobalBlockingDatabase, $wgDBname;

		// Override $wgGlobalBlockingDatabase for Wikimedia Jenkins.
		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			$wgGlobalBlockingDatabase = $wgDBname;
		}
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

		if ( $ip === null && !IPUtils::isIPAddress( $user->getName() ) ) {
			return true;
		}

		if ( $this->permissionManager->userHasAnyRight( $user, 'ipblock-exempt', 'globalblock-exempt' ) ) {
			return true;
		}

		$globalBlock = GlobalBlocking::getUserBlock( $user, $ip );
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
		$block = GlobalBlocking::getUserBlock( $user, $ip );
		if ( $block !== null ) {
			$blocked = true;
			return false;
		}
		return true;
	}

	/**
	 * @param array &$users
	 * @param array $data
	 * @param string &$error
	 *
	 * @return bool
	 */
	public function onSpecialPasswordResetOnSubmit( &$users, $data, &$error ) {
		$requestContext = RequestContext::getMain();

		if ( GlobalBlocking::getUserBlockErrors(
			$requestContext->getUser(),
			$requestContext->getRequest()->getIP()
		) ) {
			$error = 'globalblocking-blocked-nopassreset';
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
			}
			return false;
		}
		return true;
	}

	/**
	 * Creates a link to the global block log
	 * @param array &$msg Message with a link to the global block log
	 * @param string $ip The IP address to be checked
	 *
	 * @return bool true
	 */
	public function onOtherBlockLogLink( &$msg, $ip ) {
		// Fast return if it is a username. IP addresses can be blocked only.
		if ( !IPUtils::isIPAddress( $ip ) ) {
			return true;
		}

		$block = GlobalBlocking::getGlobalBlockingBlock( $ip, true );
		if ( !$block ) {
			// Fast return if not globally blocked
			return true;
		}

		$msg[] = Html::rawElement(
			'span',
			[ 'class' => 'mw-globalblock-loglink plainlinks' ],
			wfMessage( 'globalblocking-loglink', $ip )->parse()
		);
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
	public function onSpecialContributionsBeforeMainOutput(
		$userId, $user, $sp
	) {
		$name = $user->getName();
		if ( !IPUtils::isIPAddress( $name ) ) {
			return true;
		}

		$block = GlobalBlocking::getGlobalBlockingBlock( $name, true );

		if ( $block !== null ) {
			$conds = GlobalBlocking::getRangeCondition( $block->gb_address );
			$pager = new GlobalBlockListPager(
				$sp->getContext(),
				$conds,
				$sp->getLinkRenderer(),
				$this->commentFormatter
			);
			$body = $pager->formatRow( $block );

			$out = $sp->getOutput();
			$out->addHTML(
				Html::warningBox(
					$sp->msg( 'globalblocking-contribs-notice', $name )->parseAsBlock() .
					Html::rawElement( 'ul', [], $body ),
					'mw-warning-with-logexcerpt'
				)
			);
		}

		return true;
	}

	/**
	 * Adds a link on Special:Contributions to Special:GlobalBlock for privileged users.
	 * @param int $id User ID
	 * @param Title $title User page title
	 * @param array &$tools Tool links
	 * @param SpecialPage $sp Special page
	 * @return bool|void
	 */
	public function onContributionsToolLinks(
		$id, $title, &$tools, $sp
	) {
		$user = $sp->getUser();
		$linkRenderer = $sp->getLinkRenderer();
		$ip = $title->getText();

		if ( IPUtils::isIPAddress( $ip ) ) {
			if ( IPUtils::isValidRange( $ip ) ) {
				$target = IPUtils::sanitizeRange( $ip );
			} else {
				$target = IPUtils::sanitizeIP( $ip );
			}
			if ( $target === null ) {
				throw new LogicException( 'IPUtils::sanitizeIP returned null for a valid IP' );
			}
			if ( $this->permissionManager->userHasRight( $user, 'globalblock' ) ) {
				if ( GlobalBlocking::getGlobalBlockId( $ip ) === 0 ) {
					$tools['globalblock'] = $linkRenderer->makeKnownLink(
						SpecialPage::getTitleFor( 'GlobalBlock', $target ),
						$sp->msg( 'globalblocking-contribs-block' )->text()
					);
				} else {
					$tools['globalblock'] = $linkRenderer->makeKnownLink(
						SpecialPage::getTitleFor( 'GlobalBlock', $target ),
						$sp->msg( 'globalblocking-contribs-modify' )->text()
					);

					$tools['globalunblock'] = $linkRenderer->makeKnownLink(
						SpecialPage::getTitleFor( 'RemoveGlobalBlock', $target ),
						$sp->msg( 'globalblocking-contribs-remove' )->text()
					);
				}
			}
		}
	}

	/**
	 * @param array &$updateFields
	 *
	 * @return bool
	 */
	public static function onUserMergeAccountFields( array &$updateFields ) {
		$updateFields[] = [ 'global_block_whitelist', 'gbw_by', 'gbw_by_text' ];

		return true;
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
