<?php
/**
 * Log formatter for gblblock whitelist entries
 */
class GlobalBlockWhitelistLogFormatter extends LogFormatter {
	protected function getMessageKey() {
		$subtype = $this->entry->getSubtype();

		if ( $subtype === 'whitelist' ) {
			$key = 'globalblocking-logentry-whitelist';
		} elseif ( $subtype === 'dwhitelist' ) {
			$key = 'globalblocking-logentry-dewhitelist';
		} else {
			throw new UnexpectedValueException( "Unknown log subtype: $subtype" );
		}

		return $key;
	}
}
