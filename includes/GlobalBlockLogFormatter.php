<?php

namespace MediaWiki\Extension\GlobalBlocking;

use LogEntry;
use LogFormatter;
use LogPage;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingLinkBuilder;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockingUserVisibilityLookup;
use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserIdentityValue;
use UnexpectedValueException;
use Wikimedia\IPUtils;

/**
 * Log formatter for gblblock/* entries
 */
class GlobalBlockLogFormatter extends LogFormatter {

	private UserIdentityLookup $userIdentityLookup;
	private GlobalBlockingLinkBuilder $globalBlockingLinkBuilder;
	private GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup;

	/**
	 * @param LogEntry $entry
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param GlobalBlockingLinkBuilder $globalBlockingLinkBuilder
	 * @param GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	 */
	public function __construct(
		LogEntry $entry,
		UserIdentityLookup $userIdentityLookup,
		GlobalBlockingLinkBuilder $globalBlockingLinkBuilder,
		GlobalBlockingUserVisibilityLookup $globalBlockingUserVisibilityLookup
	) {
		parent::__construct( $entry );
		$this->userIdentityLookup = $userIdentityLookup;
		$this->globalBlockingLinkBuilder = $globalBlockingLinkBuilder;
		$this->globalBlockingUserVisibilityLookup = $globalBlockingUserVisibilityLookup;
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

		$targetUserIdentity = $this->getUserIdentityForTarget();
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
					// Construct the block flags
					$flags = [];
					if ( $params[5] !== '' ) {
						$flags[] = $params[5];
					}
					// All legacy global blocking logs disable account creation and have autoblocks disabled,
					// so mark them as such.
					$flags[] = $this->msg( 'globalblocking-block-flag-account-creation-disabled' )->text();
					$flags[] = $this->msg( 'globalblocking-block-flag-autoblock-disabled' )->text();
					// Wrap the flags in parentheses.
					$params[5] = $this->msg(
						'parentheses',
						$this->context->getLanguage()->commaList( $flags )
					)->text();
				} else {
					$flags = [];
					if ( $params[4] !== '' ) {
						$flags[] = $params[4];
					}
					// All legacy global blocking logs disable account creation and have autoblocks disabled,
					// so mark them as such.
					$flags[] = $this->msg( 'globalblocking-block-flag-account-creation-disabled' )->text();
					$flags[] = $this->msg( 'globalblocking-block-flag-autoblock-disabled' )->text();
					$params[4] = $this->context->getLanguage()->commaList( $flags );
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
			// We have to do an inverse check here, because before T17273 all blocks disabled account creation
			// and as such the checking for the existence of a flag won't work for pre-MW 1.43 logs.
			if ( !in_array( 'allow-account-creation', $params[5] ) ) {
				$flags[] = $this->msg( 'globalblocking-block-flag-account-creation-disabled' )->text();
			}
			// We have to do an inverse check here, because before T374853 no flag was set to indicate if
			// autoblocks were enabled. Also autoblocks cannot be enabled for IP blocks, so skip indicating this
			// when the target is an IP or IP range.
			if (
				!in_array( 'enable-autoblock', $params[5] ) &&
				!IPUtils::isIPAddress( $targetUserIdentity->getName() )
			) {
				$flags[] = $this->msg( 'globalblocking-block-flag-autoblock-disabled' )->text();
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

		$canViewTarget = $this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser(
			$targetUserIdentity->getName(), $this->context->getAuthority()
		);

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
			if ( GlobalBlockLookup::isAGlobalBlockId( $targetUserIdentity->getName() ) ) {
				// Override the third-parameter to be the global block ID as plaintext, as no wikilink can be
				// generated for an ID.
				$params[2] = Message::plaintextParam( $targetUserIdentity->getName() );
			} else {
				// Overwrite the third parameter (index 2) with a user link to provide a talk page link and link the
				// contributions page for IP addresses.
				$params[2] = Message::rawParam(
					$this->makeUserLink( $targetUserIdentity, Linker::TOOL_LINKS_NOBLOCK )
				);
			}
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
			!$this->globalBlockingUserVisibilityLookup->checkAuthorityCanSeeUser(
				$targetUserIdentity->getName(), $this->context->getAuthority()
			) ||
			!$this->canView( LogPage::DELETED_ACTION )
		) {
			// Don't show the action links if the current authority cannot view the target of the block.
			return '';
		}
		// Get the action links for the log entry which are the same as those used on Special:GlobalBlockList.
		return $this->globalBlockingLinkBuilder->getActionLinks(
			$this->context->getAuthority(), $targetUserIdentity->getName(), $this->context
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
}
