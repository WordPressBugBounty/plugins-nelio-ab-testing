/**
 * External dependencies
 */
import type { Dict } from '@nab/types';

/**
 * Internal dependencies
 */
import { initJavaScriptPreviewer } from './javascript-previewer';

const hasNab = ( x: unknown ): x is { nab: Dict } =>
	!! x && 'object' === typeof x && 'nab' in x;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
( window as any as Dict ).nab = {
	...( hasNab( window ) ? window.nab : {} ),
	initJavaScriptPreviewer,
};
