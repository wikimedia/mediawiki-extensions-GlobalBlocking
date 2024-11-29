/**
 * This is to undo the CSS styles added to hide elements which are hidden using 'hide-if'.
 * This is done to avoid the elements flashing into and then out of existence on page load.
 */
module.exports = function () {
	$( '.mw-htmlform-hide-if' ).addClass( 'mw-globalblocking-js-loaded' );
};
