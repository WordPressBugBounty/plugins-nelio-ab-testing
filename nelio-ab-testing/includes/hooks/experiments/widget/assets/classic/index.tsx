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
import { filter } from 'lodash';
import { appendSibling } from '@nab/utils';
import type { AlternativeId, ExperimentId } from '@nab/types';

/**
 * Internal dependencies
 */
import {
	getAlternativeSidebars,
	hideAllSidebars,
	organizeSidebars,
} from './utils';
import { DuplicateControlWidgetsButton } from './duplicate-control-widgets-button';

type Settings = {
	readonly sidebars: ReadonlyArray< string >;
	readonly experiment: ExperimentId;
	readonly alternative: AlternativeId;
	readonly links: {
		readonly experimentUrl: string;
	};
};

export function initControlEdition(): void {
	organizeSidebars();
	getAlternativeSidebars().forEach( ( sidebar ) => {
		if ( sidebar.parentElement ) {
			sidebar.parentElement.style.display = 'none';
		} //end if
	} );
} //end initControlEdition()

export function initAlternativeEdition( settings: Settings ): void {
	organizeSidebars();
	hideAllSidebars();

	const sidebars: ReadonlyArray< HTMLElement > = filter(
		settings.sidebars.map( ( sidebarId ) =>
			document.getElementById( sidebarId )
		),
		( el ): el is HTMLElement => !! el
	);
	sidebars.forEach( ( sidebar ) => {
		if ( sidebar.parentElement ) {
			sidebar.parentElement.style.display = 'block';
		} //end if
	} );

	renamePage();
	destroyLivePreviewTitleAction();
	addAlternativeTitleActions( settings );
} //end initAlternativeEdition()

// =======
// HELPERS
// =======

function destroyLivePreviewTitleAction() {
	Array.from( document.querySelectorAll( '.page-title-action' ) ).forEach(
		( button ) => button.remove()
	);
} //end destroyLivePreviewTitleAction()

function renamePage() {
	const title = document.querySelector(
		'#wpbody-content .wrap .wp-heading-inline'
	);
	if ( title ) {
		title.textContent = _x( 'Widget Variant', 'text', 'nelio-ab-testing' );
	} //end if
} //end renamePage()

function addAlternativeTitleActions( settings: Settings ) {
	const title = document.querySelector< HTMLElement >(
		'#wpbody-content .wrap .wp-heading-inline'
	);

	const aux = document.createElement( 'div' );
	render(
		<DuplicateControlWidgetsButton
			experiment={ settings.experiment }
			alternative={ settings.alternative }
		/>,
		aux
	);
	const duplicate = aux.children[ 0 ] as HTMLElement | null;

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
		if ( duplicate ) {
			appendSibling( duplicate, title );
		} //end if
	} //end if
} //end addAlternativeTitleActions()
