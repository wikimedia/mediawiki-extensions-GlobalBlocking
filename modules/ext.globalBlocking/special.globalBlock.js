/**
 * JavaScript for Special:GlobalBlock.
 * This is a modified version of mediawiki.special.block.js from mediawiki/core.
 */
module.exports = function () {
	// Like OO.ui.infuse(), but if the element doesn't exist,
	// return null instead of throwing an exception.
	function infuseIfExists( $el ) {
		if ( !$el.length ) {
			return null;
		}
		return OO.ui.infuse( $el );
	}

	var blockTargetWidget, anonOnlyWidget, alsoLocalSoftWidget, localBlockWidget, enableAutoblockWidget;

	function preserveSelectedStateOnDisable( widget ) {
		var widgetWasSelected;

		if ( !widget ) {
			return;
		}

		// 'disable' event fires if disabled state changes
		widget.on( 'disable', function ( disabled ) {
			if ( disabled ) {
				// Disabling an enabled widget
				// Save selected and set selected to false
				widgetWasSelected = widget.isSelected();
				widget.setSelected( false );
			} else {
				// Enabling a disabled widget
				// Set selected to the saved value
				if ( widgetWasSelected !== undefined ) {
					widget.setSelected( widgetWasSelected );
				}
				widgetWasSelected = undefined;
			}
		} );
	}

	function updateBlockOptions() {
		var blockTarget = blockTargetWidget.getValue().trim(),
			isEmpty = blockTarget === '',
			isIp = mw.util.isIPAddress( blockTarget, true );

		anonOnlyWidget.setDisabled( !isIp && !isEmpty );
		alsoLocalSoftWidget.setDisabled( !isIp && !isEmpty );
		enableAutoblockWidget.setDisabled( isIp && !isEmpty );
	}

	// This code is also loaded on the "block succeeded" page where there is no form,
	// so check for block target widget; if it exists, the form is present
	blockTargetWidget = infuseIfExists( $( '#mw-globalblock-target' ) );

	if ( blockTargetWidget ) {
		// Always present if blockTargetWidget is present
		anonOnlyWidget = OO.ui.infuse( $( '#mw-globalblock-anon-only' ) );
		enableAutoblockWidget = OO.ui.infuse( $( '#mw-globalblock-enable-autoblock' ) );

		blockTargetWidget.on( 'change', updateBlockOptions );

		// When disabling checkboxes, preserve their selected state in case they are re-enabled
		preserveSelectedStateOnDisable( anonOnlyWidget );
		preserveSelectedStateOnDisable( enableAutoblockWidget );

		localBlockWidget = infuseIfExists( $( '#mw-globalblock-local' ) );
		if ( localBlockWidget ) {
			alsoLocalSoftWidget = OO.ui.infuse( $( '#mw-globalblock-local-soft' ) );
			localBlockWidget.on( 'change', updateBlockOptions );
			preserveSelectedStateOnDisable( alsoLocalSoftWidget );
		}

		updateBlockOptions();
	}
};
