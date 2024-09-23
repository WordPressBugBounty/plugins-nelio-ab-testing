/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { render } from '@safe-wordpress/element';

/**
 * External dependencies
 */
import type { ExperimentId, AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import { hideAlternativeSidebars, hideAllSidebarsBut } from './utils';
import { DuplicateControlWidgetsButton } from './duplicate-control-widgets-button';

type Settings = {
	readonly sidebars: ReadonlyArray< string >;
	readonly experiment: ExperimentId;
	readonly alternative: AlternativeId;
};

export function initControlEdition(): void {
	hideAlternativeSidebars();
} //end initControlEdition()

export function initAlternativeEdition( settings: Settings ): void {
	hideAllSidebarsBut( settings.sidebars );
	addMainButton( settings );
} //end if

// NOTE. Add “Dupliacte Widgets” button quick and dirty.
function addMainButton( settings: Settings ) {
	const add = ( actions: Element ) => {
		const child = actions.children[ 0 ];
		if ( ! child ) {
			return;
		} //end if

		// phpcs:ignore
		const holder = document.createElement( 'div' );
		actions.insertBefore( holder, child );
		render(
			<DuplicateControlWidgetsButton
				experiment={ settings.experiment }
				alternative={ settings.alternative }
			/>,
			holder
		);
	};

	let i = 0;
	const check = (): void => {
		const actions = document.querySelector(
			'.edit-widgets-header__actions'
		);
		if ( ! actions?.children?.length && i < 40 ) {
			setTimeout( check, ++i * 250 );
			return;
		} //end if
		if ( actions ) {
			add( actions );
		} //end if
	};
	check();
} //end initAlternativeEdition()
