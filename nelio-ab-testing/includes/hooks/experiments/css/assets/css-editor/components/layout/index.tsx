/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useSelect } from '@safe-wordpress/data';
import {
	addQueryArgs,
	getQueryArgs,
	removeQueryArgs,
} from '@safe-wordpress/url';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { indexOf, map } from 'lodash';
import { usePageAttribute } from '@nab/data';
import { store as NAB_EDITOR } from '@nab/editor';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { Sidebar } from '../sidebar';
import { CssPreview } from '../preview';

export type LayoutProps = {
	readonly alternativeId: AlternativeId;
};

export const Layout = ( { alternativeId }: LayoutProps ): JSX.Element => {
	const [ areControlsVisible ] = usePageAttribute(
		'css-preview/areControlsVisible',
		true
	);
	const cssValue = useCssValue( alternativeId );
	const previewUrl = usePreviewUrl();
	return (
		<div className="nab-css-editor">
			<Sidebar
				className={ classnames( {
					'nab-css-editor__sidebar': true,
					'nab-css-editor__sidebar--is-collapsed':
						! areControlsVisible,
				} ) }
				alternativeId={ alternativeId }
			/>

			<CssPreview
				key="nab-css-editor__preview"
				className={ classnames( {
					'nab-css-editor__preview': true,
					'nab-css-editor__preview--is-fullscreen':
						! areControlsVisible,
				} ) }
				previewUrl={ previewUrl }
				value={ cssValue }
			/>
		</div>
	);
};

// =====
// HOOKS
// =====

const useCssValue = ( alternativeId: AlternativeId ) =>
	useSelect(
		( select ) =>
			select( NAB_EDITOR ).getAlternative< { css: string } >(
				alternativeId
			)?.attributes?.css || ''
	);

const usePreviewUrl = () =>
	useSelect( ( select ) => {
		const { getAlternative } = select( NAB_EDITOR );
		const alternative = getAlternative( 'control' );
		if ( ! alternative ) {
			return;
		} //end if

		const { getAlternatives, getExperimentId } = select( NAB_EDITOR );
		const alternativeId = ( getQueryArgs( document.location.href )
			.alternative || '' ) as unknown as string;
		const alternatives = getAlternatives();
		const experimentId = getExperimentId();
		const alternativeIndex = indexOf(
			map( alternatives, 'id' ),
			alternativeId
		);

		const baseUrl = !! alternative.links
			? alternative.links.preview || '/'
			: '/';
		const cleanUrl = removeQueryArgs(
			baseUrl,
			'nab-preview',
			'experiment',
			'alternative',
			'timestamp',
			'nabnonce'
		);
		return addQueryArgs( cleanUrl, {
			'nab-css-previewer': `${ experimentId }:${ alternativeIndex }`,
		} );
	} );
