'use strict';

const specialGlobalBlock = require( '../../../modules/ext.globalBlocking/special.globalBlock.js' );

QUnit.module( 'ext.globalBlocking.special.globalBlock', QUnit.newMwEnvironment() );

function setUpDocumentForTest() {
	// eslint-disable-next-line no-jquery/no-global-selector
	const $qunitFixture = $( '#qunit-fixture' );

	// Create the barebones HTML structure added by SpecialInvestigate::addBlockForm
	const node = document.createElement( 'div' );
	// Add the target input to the DOM. The data-ooui attribute value
	// is hardcoded as there is no way to get it in a QUnit test context.
	const $targetInput = new mw.widgets.UserInputWidget( {
		id: 'mw-globalblock-target'
	} ).$element;
	$targetInput.attr(
		'data-ooui',
		'{"_":"mw.widgets.UserInputWidget","$overlay":true,"autofocus":true,"name":"wpAddress",' +
		'"inputId":"ooui-php-1","indicator":"required","required":true}'
	);
	node.appendChild( $targetInput[ 0 ] );
	// Add the enable autoblocks checkbox to the DOM.
	const $enableAutoblockCheckbox = new OO.ui.CheckboxInputWidget( {
		id: 'mw-globalblock-enable-autoblock'
	} ).$element;
	$enableAutoblockCheckbox.attr(
		'data-ooui',
		'{"_":"OO.ui.CheckboxInputWidget","name":"wpAutoBlock","value":"1","inputId":"ooui-php-4","required":false}'
	);
	node.appendChild( $enableAutoblockCheckbox[ 0 ] );
	// Add the Anon-only checkbox to the DOM.
	const $anonOnlyCheckbox = new OO.ui.CheckboxInputWidget( {
		id: 'mw-globalblock-anon-only'
	} ).$element;
	$anonOnlyCheckbox.attr(
		'data-ooui',
		'{"_":"OO.ui.CheckboxInputWidget","name":"wpAnonOnly","value":"1","inputId":"ooui-php-4","required":false}'
	);
	node.appendChild( $anonOnlyCheckbox[ 0 ] );
	// Add the local block checkbox to the DOM.
	const $localBlockCheckbox = new OO.ui.CheckboxInputWidget( {
		id: 'mw-globalblock-local'
	} ).$element;
	$localBlockCheckbox.attr(
		'data-ooui',
		'{"_":"OO.ui.CheckboxInputWidget","name":"wpAlsoLocal","value":"1","inputId":"ooui-php-7","required":false}'
	);
	node.appendChild( $localBlockCheckbox[ 0 ] );
	// Add the local block soft block checkbox to the DOM.
	const $localSoftCheckbox = new OO.ui.CheckboxInputWidget( {
		id: 'mw-globalblock-local-soft'
	} ).$element;
	$localSoftCheckbox.attr(
		'data-ooui',
		'{"_":"OO.ui.CheckboxInputWidget","name":"wpAlsoLocalSoft","value":"1","inputId":"ooui-php-5","required":false}'
	);
	node.appendChild( $localSoftCheckbox[ 0 ] );
	// Add the barebones HTML structure to the QUnit fixture element.
	$qunitFixture.html( node );
	return $( node );
}

QUnit.test( 'Test checkbox visibility for a variety of targets', ( assert ) => {
	// Set the HTML that is added by Special:GlobalBlock.
	const $node = setUpDocumentForTest();

	// Call the function
	specialGlobalBlock();

	// Assert the state of the checkboxes for a variety of target input values
	const cases = require( './cases/checkboxDisabledState.json' );
	const anonOnlyWidget = OO.ui.infuse( $( '#mw-globalblock-anon-only', $node ) );
	const alsoLocalSoftWidget = OO.ui.infuse( $( '#mw-globalblock-local-soft', $node ) );
	const targetWidget = OO.ui.infuse( $( '#mw-globalblock-target', $node ) );
	const enableAutoblockWidget = OO.ui.infuse( $( '#mw-globalblock-enable-autoblock', $node ) );
	cases.forEach( ( caseItem ) => {
		targetWidget.setValue( caseItem.target );
		assert.strictEqual(
			caseItem.enableAutoblocksCheckboxShouldBeDisabled,
			enableAutoblockWidget.isDisabled(),
			caseItem.msg + ' for enable autoblocks checkbox'
		);
		assert.strictEqual(
			caseItem.anonOnlyCheckboxShouldBeDisabled,
			anonOnlyWidget.isDisabled(),
			caseItem.msg + ' for anon-only checkbox'
		);
		assert.strictEqual(
			caseItem.alsoLocalSoftCheckboxShouldBeDisabled,
			alsoLocalSoftWidget.isDisabled(),
			caseItem.msg + ' for local soft block checkbox'
		);
	} );
} );
