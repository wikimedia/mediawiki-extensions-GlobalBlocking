( function () {
	// Only include the special.globalblock.js file if the current page is Special:GlobalBlock
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) === 'GlobalBlock' ) {
		require( './special.globalBlock.js' )();
	}
	if ( [ 'MassGlobalBlock', 'GlobalBlock' ].includes( mw.config.get( 'wgCanonicalSpecialPageName' ) ) ) {
		require( './preventFlashFromHideIfFields.js' )();
	}
}() );
