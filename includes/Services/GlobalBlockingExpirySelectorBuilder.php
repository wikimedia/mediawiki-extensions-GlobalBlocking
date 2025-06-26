<?php

namespace MediaWiki\Extension\GlobalBlocking\Services;

use MediaWiki\Xml\XmlSelect;
use MessageLocalizer;

/**
 * Provides a list of expiry options which can be displayed in a form for use when globally blocking.
 *
 * @since 1.44
 */
class GlobalBlockingExpirySelectorBuilder {

	/**
	 * Get an array of suggested block durations for display in a GlobalBlocking block form.
	 *
	 * Retrieved from 'globalblocking-expiry-options'. If this message is disabled (the default), then
	 * retrieve it from SpecialBlock's 'ipboptions' message.
	 *
	 * @return array<string,string> Expiry options, empty if both messages are disabled.
	 */
	public function buildExpirySelector( MessageLocalizer $messageLocalizer ): array {
		$msg = $messageLocalizer->msg( 'globalblocking-expiry-options' )->inContentLanguage();
		if ( $msg->isDisabled() ) {
			$msg = $messageLocalizer->msg( 'ipboptions' )->inContentLanguage();
		}
		return $msg->isDisabled() ? [] : XmlSelect::parseOptionsMessage( $msg->text() );
	}

}
