/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useInstanceId } from '@safe-wordpress/compose';
import { useDispatch, useSelect } from '@safe-wordpress/data';
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
import { store as NAB_DATA, usePageAttribute } from '@nab/data';
import { store as NAB_EDITOR } from '@nab/editor';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { Sidebar } from '../sidebar';
import { JavaScriptPreview } from '../preview';

export type LayoutProps = {
	readonly alternativeId: AlternativeId;
};

export const Layout = ( { alternativeId }: LayoutProps ): JSX.Element => {
	const instanceId = useInstanceId( Layout );
	const iframeId = `nab-javascript-previewer__iframe-${ instanceId }`;
	const [ areControlsVisible ] = usePageAttribute(
		'javascript-preview/areControlsVisible',
		true
	);
	const javascriptValue = useJavaScriptValue( alternativeId );
	const previewUrl = usePreviewUrl();
	const [ isSaving, save ] = useSave( iframeId );

	return (
		<div className="nab-javascript-editor">
			<Sidebar
				className={ classnames( {
					'nab-javascript-editor__sidebar': true,
					'nab-javascript-editor__sidebar--is-collapsed':
						! areControlsVisible,
				} ) }
				alternativeId={ alternativeId }
				isSaving={ isSaving }
				save={ save }
			/>

			<JavaScriptPreview
				key="nab-javascript-editor__preview"
				className={ classnames( {
					'nab-javascript-editor__preview': true,
					'nab-javascript-editor__preview--is-fullscreen':
						! areControlsVisible,
				} ) }
				iframeId={ iframeId }
				isSaving={ isSaving }
				previewUrl={ previewUrl }
				value={ javascriptValue }
			/>
		</div>
	);
};

// =====
// HOOKS
// =====

const useJavaScriptValue = ( alternativeId: AlternativeId ) =>
	useSelect(
		( select ) =>
			select( NAB_EDITOR ).getAlternative< { code: string } >(
				alternativeId
			)?.attributes?.code || ''
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
			'nab-javascript-previewer': `${ experimentId }:${ alternativeIndex }`,
		} );
	} );

const useSave = ( iframeId: string ) => {
	const isSaving = useSelect(
		( select ) =>
			!! select( NAB_DATA ).getPageAttribute(
				'javascript-preview/isLoading'
			)
	);
	const { setPageAttribute } = useDispatch( NAB_DATA );

	const { saveExperiment } = useDispatch( NAB_EDITOR );
	const save = () => {
		void setPageAttribute( 'javascript-preview/isLoading', true );
		void saveExperiment().then( () => {
			const iframe = document.getElementById(
				iframeId
			) as HTMLIFrameElement;
			iframe?.contentWindow?.location.reload();
			setTimeout(
				() => setPageAttribute( 'javascript-preview/isLoading', false ),
				5000
			);
		} );
	};
	return [ isSaving, save ] as const;
};
