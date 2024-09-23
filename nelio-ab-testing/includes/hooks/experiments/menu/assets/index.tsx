/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { render } from '@safe-wordpress/element';
import { _x } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import '@nab/data';
import { appendSibling } from '@nab/utils';
import type { AlternativeId, Dict, ExperimentId } from '@nab/types';

/**
 * Internal dependencies
 */
import { DuplicateControlMenuButton } from './duplicate-control-menu-button';

type Settings = {
	readonly experiment: ExperimentId;
	readonly alternative: AlternativeId;
	readonly links: {
		readonly experimentUrl: string;
	};
};

function initAlternativeEdition( settings: Settings ) {
	renamePage();
	renameUpdateMessage();
	destroyUnnecessaryUIElements();
	addAlternativeTitleActions( settings );
} //end initAlternativeEdition()

// =======
// HELPERS
// =======

const hasNab = ( x: unknown ): x is { nab: Dict } =>
	!! x && 'object' === typeof x && 'nab' in x;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
( window as any as Dict ).nab = {
	...( hasNab( window ) ? window.nab : {} ),
	initAlternativeEdition,
};

function renamePage() {
	const title = document.querySelector< HTMLElement >(
		'#wpbody-content .wrap .wp-heading-inline'
	);
	if ( title ) {
		title.textContent = _x( 'Menu Variant', 'text', 'nelio-ab-testing' );
	} //end if
} //end renamePage()

function renameUpdateMessage() {
	Array.from(
		document.querySelectorAll< HTMLElement >( '#message.updated.notice' )
	).forEach( ( notice ) => {
		const text = _x(
			'<strong>Menu variant</strong> has been updated.',
			'text',
			'nelio-ab-testing'
		);
		notice.innerHTML = `<p>${ text }</p>`; // phpcs:ignore
	} );
} //end renameUpdateMessage()

function destroyUnnecessaryUIElements() {
	Array.from(
		document.querySelectorAll< HTMLElement >( '.wrap > .page-title-action' )
	).forEach( ( button ) => button.remove() );
	Array.from(
		document.querySelectorAll< HTMLElement >( '.wrap > .nav-tab-wrapper' )
	).forEach( ( tabs ) => tabs.remove() );
	Array.from(
		document.querySelectorAll< HTMLElement >( '.wrap > .manage-menus' )
	).forEach( ( notice ) => notice.remove() );

	Array.from(
		document.querySelectorAll< HTMLElement >(
			'#menu-name, .menu-name-label'
		)
	).forEach( ( node ) => ( node.style.display = 'none' ) );
	Array.from(
		document.querySelectorAll< HTMLElement >(
			'#nav-menu-footer .delete-action'
		)
	).forEach( ( node ) => node.remove() );

	Array.from(
		document.querySelectorAll< HTMLElement >( '.menu-settings' )
	).forEach( ( node ) => node.remove() );
} //end destroyUnnecessaryUIElements()

function addAlternativeTitleActions( settings: Settings ) {
	const title = document.querySelector< HTMLElement >(
		'#wpbody-content .wrap .wp-heading-inline'
	);

	const duplicate = document.createElement( 'span' );
	render(
		<DuplicateControlMenuButton
			experiment={ settings.experiment }
			alternative={ settings.alternative }
		/>,
		duplicate
	);

	const backToTest = document.createElement( 'a' );
	backToTest.className = 'page-title-action';
	backToTest.textContent = _x(
		'Back to Test',
		'command',
		'nelio-ab-testing'
	);
	backToTest.setAttribute( 'href', settings.links.experimentUrl );

	if ( title ) {
		appendSibling( backToTest, title );
		appendSibling( duplicate, title );
	} //end if
} //end addAlternativeTitleActions()
