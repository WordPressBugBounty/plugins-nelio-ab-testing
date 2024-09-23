/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { _x } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { usePageAttribute } from '@nab/data';

/**
 * Internal dependencies
 */
import './style.scss';

export type JavaScriptPreviewProps = {
	readonly className?: string;
	readonly iframeId: string;
	readonly isSaving: boolean;
	readonly previewUrl?: string;
	readonly value: string;
};

export const JavaScriptPreview = ( {
	className,
	iframeId,
	isSaving,
	previewUrl,
}: JavaScriptPreviewProps ): JSX.Element => {
	const [ currentPreviewResolution ] = usePageAttribute(
		'javascript-preview/activeResolution',
		'desktop'
	);
	return (
		<div
			className={ classnames( [ className, 'nab-javascript-preview' ] ) }
		>
			<iframe
				id={ iframeId }
				className={ classnames( {
					'nab-javascript-preview__iframe': true,
					[ `nab-javascript-preview__iframe--${ currentPreviewResolution }` ]:
						true,
					'nab-javascript-preview__iframe--is-saving': isSaving,
				} ) }
				title={ _x( 'JavaScript Preview', 'text', 'nelio-ab-testing' ) }
				src={ previewUrl }
			></iframe>
		</div>
	);
};
