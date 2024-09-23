/**
 * External dependencies
 */
import '@nab/data';
import type { Dict } from '@nab/types';

/**
 * Internal dependencies
 */
import { initExperimentSummary } from './experiment-summary';
import { initProductDataMetabox } from './product-data-metabox';
import { initProductGalleryMetabox } from './product-gallery-metabox';

const hasNab = ( x: unknown ): x is { nab: Dict } =>
	!! x && 'object' === typeof x && 'nab' in x;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
( window as any as Dict ).nab = {
	...( hasNab( window ) ? window.nab : {} ),
	initExperimentSummary,
	initProductDataMetabox,
	initProductGalleryMetabox,
};
