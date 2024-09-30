/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useSelect } from '@safe-wordpress/data';
import { StrictMode } from '@safe-wordpress/element';

/**
 * External dependencies
 */
import { store as NAB_DATA } from '@nab/data';
import { EditorProvider } from '@nab/editor';
import type { AlternativeId, ExperimentId } from '@nab/types';

/**
 * Internal dependencies
 */
import { Layout } from '../layout';

export type JavaScriptEditorPageProps = {
	readonly experimentId: ExperimentId;
	readonly alternativeId: AlternativeId;
};

export const JavaScriptEditorPage = ( {
	experimentId,
	alternativeId,
}: JavaScriptEditorPageProps ): JSX.Element | null => {
	const experiment = useSelect( ( select ) =>
		select( NAB_DATA ).getExperiment( experimentId )
	);

	if ( ! experiment ) {
		return null;
	} //end if

	return (
		<StrictMode>
			<EditorProvider experiment={ experiment }>
				<Layout alternativeId={ alternativeId } />
			</EditorProvider>
		</StrictMode>
	);
};
