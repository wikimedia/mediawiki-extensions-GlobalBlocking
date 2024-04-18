<?php

namespace MediaWiki\Extension\GlobalBlocking;

use ExtensionRegistry;
use LogEntry;
use LogFormatter;
use LogPage;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Message\Message;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserIdentityValue;
use UnexpectedValueException;
use Wikimedia\IPUtils;

/**
 * Log formatter for gblblock/* entries
 */
class GlobalBlockLogFormatter extends LogFormatter {

	private UserFactory $userFactory;
	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;

	/**
	 * @param LogEntry $entry
	 * @param UserFactory $userFactory
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 */
	public function __construct(
		LogEntry $entry,
		UserFactory $userFactory,
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	) {
		parent::__construct( $entry );
		$this->userFactory = $userFactory;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
	}

	protected function getMessageKey(): string {
		$subtype = $this->entry->getSubtype();

		if ( $subtype === 'whitelist' ) {
			$key = 'globalblocking-logentry-whitelist';
		} elseif ( $subtype === 'dwhitelist' ) {
			$key = 'globalblocking-logentry-dewhitelist';
		} elseif ( $subtype === 'gunblock' ) {
			$key = 'globalblocking-logentry-unblock';
		} elseif ( $subtype === 'gblock' ) {
			// The 'gblock' subtype is used by both legacy and non-legacy log entries. However, the order of the
			// parameters between the legacy and non-legacy format is the same and as such we can use the same i18n
			// message key.
			$key = 'globalblocking-logentry-block';
		} elseif ( $subtype === 'gblock2' ) {
			$key = 'globalblocking-logentry-block-old-format';
		} elseif ( $subtype === 'modify' ) {
			if ( $this->entry->isLegacy() ) {
				$key = 'globalblocking-logentry-modify-old-format';
			} else {
				$key = 'globalblocking-logentry-modify';
			}
		} else {
			throw new UnexpectedValueException( "Unknown log subtype: $subtype" );
		}

		return $key;
	}

	/**
	 * Formats parameters intended for action message from array of all parameters.
	 * There are four hardcoded parameters:
	 *  - $1: user name with premade link
	 *  - $2: the performer of the block usable of the block used for the gender magic function
	 *  - $3: user page with a premade link
	 *  - $4: the target of the block used for the gender magic function
	 *
	 * For the 'gblock', and 'modify' subtypes when the log is not legacy, the parameters also include:
	 *  - $5: the expiration date of the block
	 *  - $6: the flags for the block in a localised comma separated list
	 *
	 * For the 'gblock2' and 'modify' subtypes when the log is legacy, the parameters also include:
	 *  - $5: the flags for the block
	 *
	 * For the 'gblock' subtype when the log is legacy, the parameters also include:
	 *  - $5: the expiration date of the block
	 *
	 * @return array
	 */
	protected function getMessageParameters(): array {
		$params = parent::getMessageParameters();

		if ( $this->entry->isLegacy() ) {
			if ( in_array( $this->entry->getSubtype(), [ 'gblock2', 'modify', 'gblock' ] ) ) {
				// If the entry parameters are in the legacy format and the log subtype has parameters defined,
				// then we need to increase the index of the parameters by one to allow space for the GENDER parameter
				// for the target.
				array_splice( $params, 3, 0, '' );
				if ( $this->entry->getSubtype() === 'gblock' ) {
					if ( !array_key_exists( 5, $params ) ) {
						// No flags may exist for the legacy format for the 'gblock' subtype.
						$params[5] = '';
					} elseif ( $params[5] === 'anon-only' ) {
						// Convert the anon-only flag into a localised message.
						$params[5] = $this->msg( 'globalblocking-block-flag-anon-only' )->text();
					}
					if ( $params[5] !== '' ) {
						// Wrap the flags in parentheses.
						$params[5] = $this->msg( 'parentheses', $params[5] )->text();
					}
				}
			}
		} elseif ( in_array( $this->entry->getSubtype(), [ 'gblock', 'modify' ] ) ) {
			if ( !wfIsInfinity( $params[4] ) ) {
				// Ignoring expiry values of 'infinity', treat the expiry parameter as a datetime parameter.
				$params[4] = Message::dateTimeParam( $params[4] );
			}
			// Convert the flags to a localised comma separated list
			$flags = [];
			if ( in_array( 'anon-only', $params[5] ) ) {
				$flags[] = $this->msg( 'globalblocking-block-flag-anon-only' )->text();
			}
			// Only display the flags if there are any set.
			if ( count( $flags ) ) {
				$params[5] = $this->msg(
					'parentheses',
					$this->context->getLanguage()->commaList( $flags )
				)->text();
			} else {
				$params[5] = '';
			}
		}

		$targetUserIdentity = $this->getUserIdentityForTarget();
		$canViewTarget = $this->checkAuthorityCanSeeUser( $targetUserIdentity );

		if ( !$canViewTarget ) {
			// If the current authority cannot view the target of the block, then replace the user link with a message
			// indicating that the target of the block is hidden.
			$params[2] = Message::rawParam( Html::element(
				'span',
				[ 'class' => 'history-deleted' ],
				$this->msg( 'rev-deleted-user' )->text()
			) );
			$params[3] = '';
		} else {
			// Overwrite the third parameter (index 2) with a user link to provide a talk page link and link the
			// contributions page for IP addresses.
			$params[2] = Message::rawParam( $this->makeUserLink( $targetUserIdentity, Linker::TOOL_LINKS_NOBLOCK ) );
			$params[3] = $targetUserIdentity->getName();
		}

		return $params;
	}

	/**
	 * Adds the action links for global block log entries which depend on what rights that the
	 * user has. This is the same as the action links used on Special:GlobalBlockList entries.
	 *
	 * @return string
	 */
	public function getActionLinks(): string {
		$targetUserIdentity = $this->getUserIdentityForTarget();
		if (
			!$this->checkAuthorityCanSeeUser( $targetUserIdentity ) ||
			!$this->canView( LogPage::DELETED_ACTION )
		) {
			// Don't show the action links if the current authority cannot view the target of the block.
			return '';
		}
		// Get the action links for the log entry which are the same as those used on Special:GlobalBlockList.
		return $this->globalBlockingLinkBuilder->getActionLinks(
			$this->context->getAuthority(), $targetUserIdentity->getName()
		);
	}

	/**
	 * Get the UserIdentity object for the target of the block referenced in the current log entry.
	 *
	 * @return UserIdentity This can be a IP address, range, or username (which exists or does not exist).
	 */
	private function getUserIdentityForTarget(): UserIdentity {
		$targetTitle = $this->entry->getTarget();
		$userText = $targetTitle->getText();
		if ( $targetTitle->getNamespace() === NS_SPECIAL ) {
			// Some very old log entries (pre-2010) have the title as the Special:Contributions page for the target.
			// In this case, the target text is the subpage of the Special:Contributions page (T362700).
			// We also cannot use Title::getSubpageText here because the NS_SPECIAL namespace does not have subpages
			// by default.
			$userText = substr( $userText, strlen( 'Contributions/' ) );
		}
		return $this->userIdentityLookup->getUserIdentityByName( $userText )
			?? UserIdentityValue::newAnonymous( $userText );
	}

	/**
	 * Returns whether the current authority can see the target of the block.
	 *
	 * @param UserIdentity $userIdentity The object returned by ::getUserIdentityForTarget
	 * @return bool
	 */
	private function checkAuthorityCanSeeUser( UserIdentity $userIdentity ): bool {
		if ( IPUtils::isIPAddress( $userIdentity->getName() ) ) {
			// IP addresses cannot be hidden, so the authority will always be able to see them.
			return true;
		}

		// Assume that the authority has the rights to see the user by default.
		$canViewTarget = true;
		$authority = $this->context->getAuthority();

		// If the user exists locally, then we can check if the user is hidden locally.
		if ( $userIdentity->isRegistered() ) {
			$user = $this->userFactory->newFromUserIdentity( $userIdentity );
			$canViewTarget = !( $user->isHidden() && !$authority->isAllowed( 'hideuser' ) );
		}

		// If CentralAuth is loaded, then we can check if the central user is hidden.
		// This is necessary if the user does not exist on this wiki but their global
		// account is hidden.
		if ( $canViewTarget && ExtensionRegistry::getInstance()->isLoaded( 'CentralAuth' ) ) {
			$centralUser = CentralAuthUser::getInstance( $userIdentity );
			$canViewTarget = !( $centralUser->isHidden() && !$authority->isAllowed( 'centralauth-suppress' ) );
		}

		return $canViewTarget;
	}
}
