/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { usePageAttribute } from '@nab/data';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { Sidebar } from '../sidebar';
import { PhpPreview } from '../preview';

export type LayoutProps = {
	readonly alternativeId: AlternativeId;
};

export const Layout = ( { alternativeId }: LayoutProps ): JSX.Element => {
	const [ areControlsVisible ] = usePageAttribute(
		'php-preview/areControlsVisible',
		true
	);
	return (
		<div className="nab-php-editor">
			<Sidebar
				className={ classnames( {
					'nab-php-editor__sidebar': true,
					'nab-php-editor__sidebar--is-collapsed':
						! areControlsVisible,
				} ) }
				alternativeId={ alternativeId }
			/>

			<PhpPreview
				key="nab-php-editor__preview"
				className={ classnames( {
					'nab-php-editor__preview': true,
					'nab-php-editor__preview--is-fullscreen':
						! areControlsVisible,
				} ) }
				alternativeId={ alternativeId }
			/>
		</div>
	);
};
