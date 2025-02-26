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
import type { ScriptAlternative } from '../../../../../../assets/src/public/types';

export function initJavaScriptPreviewer( alternative: {
	readonly run: string;
} ): void {
	disableExternalLinks();
	addParamToLocalLinks( 'nab-javascript-previewer' );

	// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment, no-eval
	const run: ScriptAlternative[ 'run' ] = eval(
		`(()=>${ alternative.run })()`
	);
	run( () => void null, {
		showContent: () => void null,
		domReady,
	} );

	const value = getQueryArgs( document.location.href )[
		'nab-javascript-previewer'
	] as unknown as string;
	if ( value ) {
		domReady( () =>
			addParamToLocalLinks( 'nab-javascript-previewer', value )
		);
	} //end if
} //end initJavaScriptPreviewer()
