/**
 * WordPress dependencies
 */
import { select, subscribe } from '@safe-wordpress/data';
import { store as BLOCK_EDITOR } from '@safe-wordpress/block-editor';
import type { BlockInstance } from '@safe-wordpress/blocks';

/**
 * External dependencies
 */
import type { Dict } from '@nab/types';

/**
 * External dependencies
 */
import { areEqual } from '@nab/utils';
import { debounce } from 'lodash';

export function hideAlternativeSidebars(): void {
	let oldSidebarIds: ReadonlyArray< string > = [];
	const run = debounce( () => {
		const sidebars = select( BLOCK_EDITOR )
			.getBlocks()
			.filter( ( { name } ) => 'core/widget-area' === name );
		const sidebarIds = sidebars.map( ( { clientId } ) => clientId );

		if ( areEqual( oldSidebarIds, sidebarIds ) ) {
			return;
		} //end if
		oldSidebarIds = sidebarIds;

		const alternativeSidebars = sidebars
			.filter( isNabAltSidebar )
			.map( ( { clientId } ) => clientId );
		hideSidebars( alternativeSidebars );
	}, 100 );
	// eslint-disable-next-line @typescript-eslint/no-unsafe-call
	subscribe( run, BLOCK_EDITOR );
	run();
} //end hideAlternativeSidebars()

export function hideAllSidebarsBut(
	exceptions: ReadonlyArray< string >
): void {
	exceptions = [ ...exceptions, 'wp_inactive_widgets' ];
	let oldSidebarIds: ReadonlyArray< string > = [];
	const run = debounce( () => {
		const sidebars = select( BLOCK_EDITOR )
			.getBlocks()
			.filter( ( { name } ) => 'core/widget-area' === name );
		const sidebarIds = sidebars.map( ( { clientId } ) => clientId );

		if ( areEqual( oldSidebarIds, sidebarIds ) ) {
			return;
		} //end if
		oldSidebarIds = sidebarIds;

		const sidebarsToHide = sidebars
			.filter(
				( { attributes: { id } } ) =>
					'string' === typeof id && ! exceptions.includes( id )
			)
			.map( ( { clientId } ) => clientId );

		hideSidebars( sidebarsToHide );
	}, 100 );
	// eslint-disable-next-line @typescript-eslint/no-unsafe-call
	subscribe( run, BLOCK_EDITOR );
	run();
} //end hideAllSidebarsBut()

// =======
// HELPERS
// =======

function hideSidebars( blockIds: ReadonlyArray< string > ): void {
	const style = document.getElementById( 'nab-widget-global-style' );
	if ( ! style ) {
		return;
	} //end if
	const css = blockIds.reduce(
		( r, id ) => `${ r }\n#block-${ id } { display: none; }`,
		''
	);
	style.textContent = css;
} //end hideSidebars()

function isNabAltSidebar( bi: BlockInstance ) {
	const { id } = bi.attributes as Dict;
	return 'string' === typeof id && id.startsWith( 'nab_alt_sidebar' );
} // end isNabAltSidebar()
