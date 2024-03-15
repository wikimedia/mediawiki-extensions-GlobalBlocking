<?php

namespace MediaWiki\Extension\GlobalBlocking;

use LogEntry;
use LogFormatter;
use MediaWiki\Linker\Linker;
use MediaWiki\Message\Message;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserIdentityValue;
use UnexpectedValueException;

/**
 * Log formatter for gblblock/* entries
 */
class GlobalBlockLogFormatter extends LogFormatter {

	private UserIdentityLookup $userIdentityLookup;

	/**
	 * @param LogEntry $entry
	 * @param UserIdentityLookup $userIdentityLookup
	 */
	public function __construct( LogEntry $entry, UserIdentityLookup $userIdentityLookup ) {
		parent::__construct( $entry );
		$this->userIdentityLookup = $userIdentityLookup;
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
				// then we need to move the fourth parameter to the fifth to make way for GENDER support message
				// parameter.
				$params[4] = $params[3];
				if ( $this->entry->getSubtype() === 'gblock' ) {
					// No flags exist for the legacy format for the 'gblock' subtype.
					$params[5] = '';
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

		$userText = $this->entry->getTarget()->getText();
		$targetUserIdentity = $this->userIdentityLookup->getUserIdentityByName( $userText )
			?? UserIdentityValue::newAnonymous( $userText );

		$params[2] = Message::rawParam( $this->makeUserLink( $targetUserIdentity, Linker::TOOL_LINKS_NOBLOCK ) );
		$params[3] = $userText;

		return $params;
	}
}
