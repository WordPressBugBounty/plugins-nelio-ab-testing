/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useDispatch, useSelect } from '@safe-wordpress/data';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { store as NAB_EDITOR } from '@nab/editor';
import { omitUndefineds } from '@nab/utils';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { SaveButton } from './save';
import { JavaScriptEditor } from './editor';
import { FooterActions } from './footer-actions';

export type SidebarProps = {
	readonly className?: string;
	readonly alternativeId: AlternativeId;
	readonly isSaving: boolean;
	readonly save: () => void;
};

export const Sidebar = ( {
	className,
	alternativeId,
	isSaving,
	save,
}: SidebarProps ): JSX.Element => {
	const [ value, setValue ] = useJavaScriptValue( alternativeId );
	return (
		<div
			className={ classnames( [
				'nab-javascript-editor-sidebar',
				className,
			] ) }
		>
			<SaveButton isSaving={ isSaving } save={ save } />
			<JavaScriptEditor value={ value } onChange={ setValue } />
			<FooterActions />
		</div>
	);
};

// =====
// HOOKS
// =====

const useJavaScriptValue = ( alternativeId: AlternativeId ) => {
	const alternative = useSelect( ( select ) =>
		select( NAB_EDITOR ).getAlternative< { code: string } >( alternativeId )
	);
	const value = alternative?.attributes?.code || '';

	const { setAlternative } = useDispatch( NAB_EDITOR );
	const setValue = ( code: string ) => {
		if ( ! alternative ) {
			return;
		} //end if
		void setAlternative( alternative.id, {
			...alternative,
			attributes: omitUndefineds( {
				...alternative.attributes,
				code,
			} ),
		} );
	};

	return [ value, setValue ] as const;
};
