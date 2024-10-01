<?php

namespace MediaWiki\Extension\GlobalBlocking\Widget;

use MediaWiki\Extension\GlobalBlocking\Services\GlobalBlockLookup;
use MediaWiki\HTMLForm\Field\HTMLUserTextField;

/**
 * A field used in GlobalBlocking special pages that extends {@link HTMLUserTextField} and differs by considering
 * global block IDs (in the format "#123") valid.
 *
 * @since 1.43
 */
class HTMLUserTextFieldAllowingGlobalBlockIds extends HTMLUserTextField {
	public function validate( $value, $alldata ) {
		if ( GlobalBlockLookup::isAGlobalBlockId( $value ) ) {
			return true;
		}
		return parent::validate( $value, $alldata );
	}
}
