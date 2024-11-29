'use strict';

const preventFlashFromHideIfFields = require(
	'../../../modules/ext.globalBlocking/preventFlashFromHideIfFields.js'
);

QUnit.module( 'ext.globalBlocking.preventFlashFromHideIfFields', QUnit.newMwEnvironment() );

QUnit.test( 'Adds mw-globalblocking-js-loaded class to relevant elements', ( assert ) => {
	// eslint-disable-next-line no-jquery/no-global-selector
	const $qunitFixture = $( '#qunit-fixture' );

	const node = document.createElement( 'div' );
	// Add a element which is a 'hide-if' field to the wrapping element
	const matchingElement = document.createElement( 'div' );
	matchingElement.className = 'mw-htmlform-hide-if';
	node.appendChild( matchingElement );
	// Add an element which is not a 'hide-if' field to the wrapping element.
	const otherElement = document.createElement( 'div' );
	otherElement.className = 'mw-htmlform-field';
	node.appendChild( otherElement );

	// Add the wrapping element to the QUnit test fixture.
	$qunitFixture.html( node );

	// Call our method under test and assert that it worked.
	preventFlashFromHideIfFields();

	assert.strictEqual(
		matchingElement.className,
		'mw-htmlform-hide-if mw-globalblocking-js-loaded',
		'Matching element had JS loaded class added to it'
	);
	assert.strictEqual(
		otherElement.className,
		'mw-htmlform-field',
		'Other element had no changes performed to it'
	);
} );
