/**
 * External dependencies
 */
import { appendSibling } from '@nab/utils';

export function hideAllSidebars(): void {
	Array.from(
		document.querySelectorAll< HTMLElement >(
			'.sidebars-column-1 > .widgets-holder-wrap, .sidebars-column-2 > .widgets-holder-wrap'
		)
	).forEach( ( node ) => ( node.style.display = 'none' ) );
} //end hideAllSidebars()

export function getAlternativeSidebars(): ReadonlyArray< HTMLElement > {
	return Array.from(
		document.querySelectorAll< HTMLElement >( 'div[id^=nab_alt_sidebar_]' )
	);
} //end getAlternativeSidebars()

export function organizeSidebars(): void {
	const sidebars = getAlternativeSidebars();
	sidebars.forEach( ( sidebar ) =>
		moveSidebarNodeAfterItsControlVersion( sidebar )
	);
} //end organizeSidebars()

// =======
// HELPERS
// =======

function moveSidebarNodeAfterItsControlVersion( sidebar: HTMLElement ) {
	const controlId = extractControlId( sidebar.getAttribute( 'id' ) || '' );
	if ( ! controlId ) {
		return;
	} //end if

	const control = document.getElementById( controlId );
	if ( ! control ) {
		return;
	} //end if

	if ( sidebar.parentElement && control.parentElement ) {
		appendSibling( sidebar.parentElement, control.parentElement );
	} //end if
} //end moveSidebarNodeAfterItsControlVersion()

function extractControlId( sidebarId: string ): string {
	const controlId = sidebarId.replace(
		/^nab_alt_sidebar_[0-9]+_([^-]+-){4}[^-]+_/,
		''
	);
	if ( controlId === sidebarId ) {
		return '';
	} //end if

	return controlId;
} //end extractControlId()
