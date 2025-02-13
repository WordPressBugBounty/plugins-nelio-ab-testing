/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useState } from '@safe-wordpress/element';
import { useDispatch, useSelect } from '@safe-wordpress/data';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { CodeEditor } from '@nab/components';
import { store as NAB_EDITOR } from '@nab/editor';
import { omitUndefineds } from '@nab/utils';
import type { AlternativeId, Maybe } from '@nab/types';
import type { EditorView } from '@uiw/react-codemirror';

/**
 * Internal dependencies
 */
import './style.scss';
import type { AlternativeAttributes } from '../../../../../../../../packages/experiment-library/php/types';

export type EditorProps = {
	readonly alternativeId: AlternativeId;
};

export const Editor = ( { alternativeId }: EditorProps ): JSX.Element => {
	const [ value, setValue ] = usePhpValue( alternativeId );
	const [ editorView, setEditorView ] = useState< Maybe< EditorView > >();

	/* eslint-disable jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions */
	return (
		<div
			className="nab-php-editor-sidebar__editor"
			onClick={ () => editorView?.focus() }
		>
			<CodeEditor
				language="php"
				before="<?php"
				value={ value }
				onChange={ setValue }
				readOnly={ false }
				onCreateEditor={ setEditorView }
				placeholder={
					_x(
						'Write your PHP code here…',
						'user',
						'nelio-ab-testing'
					) +
					'\n\n' +
					sprintf(
						/* translators: function names */
						_x(
							'We highly recommend using WordPress hooks like “%1$s” and “%2$s” to ensure a seamless integration with your setup.',
							'text',
							'nelio-ab-testing'
						),
						'add_action',
						'add_filter'
					)
				}
			/>
		</div>
	);
	/* eslint-enable jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions */
};

// =====
// HOOKS
// =====

const usePhpValue = ( alternativeId: AlternativeId ) => {
	const alternative = useSelect( ( select ) =>
		select( NAB_EDITOR ).getAlternative< AlternativeAttributes >(
			alternativeId
		)
	);
	const value = alternative?.attributes?.snippet || '';

	const { setAlternative } = useDispatch( NAB_EDITOR );
	const setValue = ( snippet: string ) => {
		if ( ! alternative ) {
			return;
		} //end if
		void setAlternative( alternative.id, {
			...alternative,
			attributes: omitUndefineds( {
				...alternative.attributes,
				validateSnippet: true,
				snippet,
			} ),
		} );
	};

	return [ value, setValue ] as const;
};
