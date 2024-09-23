/**
 * WordPress dependencies
 */
import domReady from '@safe-wordpress/dom-ready';
import { getQueryArgs } from '@safe-wordpress/url';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	disableExternalLinks,
	addParamToLocalLinks,
} from '../../../../../../packages/utils/links';

export function initCssPreviewer(): void {
	window.addEventListener( 'message', previewCssInMessage, false );

	disableExternalLinks();
	const value = getQueryArgs( document.location.href )[
		'nab-css-previewer'
	] as unknown as string;
	if ( value ) {
		domReady( () => addParamToLocalLinks( 'nab-css-previewer', value ) );
	} //end if
} //end initCssPreviewer()

// =======
// HELPERS
// =======

type Message = {
	readonly data?: {
		readonly type: 'css-preview';
		readonly value: string;
	};
};

function previewCssInMessage( message: Message ): void {
	const action = message.data;
	if ( 'css-preview' !== action?.type ) {
		return;
	} //end if

	const style = document.getElementById( 'nab-css-style' );
	if ( style ) {
		style.innerHTML = action.value;
	} //end if
} //end previewCssInMessage()
