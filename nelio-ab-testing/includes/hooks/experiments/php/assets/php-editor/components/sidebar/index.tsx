/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { Editor } from './editor';
import { Error } from './error';
import { SaveButton } from './save';
import { FooterActions } from './footer-actions';

export type SidebarProps = {
	readonly className?: string;
	readonly alternativeId: AlternativeId;
};

export const Sidebar = ( {
	className,
	alternativeId,
}: SidebarProps ): JSX.Element => (
	<div className={ classnames( [ 'nab-php-editor-sidebar', className ] ) }>
		<SaveButton />
		<Editor alternativeId={ alternativeId } />
		<Error alternativeId={ alternativeId } />
		<FooterActions />
	</div>
);
