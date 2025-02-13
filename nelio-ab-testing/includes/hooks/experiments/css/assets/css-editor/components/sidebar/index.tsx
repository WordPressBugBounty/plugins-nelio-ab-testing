/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useDispatch, useSelect } from '@safe-wordpress/data';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { CodeEditor } from '@nab/components';
import { store as NAB_EDITOR } from '@nab/editor';
import { omitUndefineds } from '@nab/utils';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { SaveButton } from './save';
import { FooterActions } from './footer-actions';

export type SidebarProps = {
	readonly className?: string;
	readonly alternativeId: AlternativeId;
};

export const Sidebar = ( {
	className,
	alternativeId,
}: SidebarProps ): JSX.Element => {
	const [ value, setValue ] = useCssValue( alternativeId );
	return (
		<div
			className={ classnames( [ 'nab-css-editor-sidebar', className ] ) }
		>
			<SaveButton />

			<CodeEditor
				language="css"
				className="nab-css-editor-sidebar__editor"
				value={ value }
				onChange={ setValue }
			/>

			<FooterActions />
		</div>
	);
};

// =====
// HOOKS
// =====

const useCssValue = ( alternativeId: AlternativeId ) => {
	const alternative = useSelect( ( select ) =>
		select( NAB_EDITOR ).getAlternative< { css: string } >( alternativeId )
	);
	const value = alternative?.attributes?.css || '';

	const { setAlternative } = useDispatch( NAB_EDITOR );
	const setValue = ( css: string ) => {
		if ( ! alternative ) {
			return;
		} //end if
		void setAlternative( alternative.id, {
			...alternative,
			attributes: omitUndefineds( {
				...alternative.attributes,
				css,
			} ),
		} );
	};

	return [ value, setValue ] as const;
};
