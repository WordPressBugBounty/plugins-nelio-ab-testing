/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useInstanceId } from '@safe-wordpress/compose';
import { useEffect, useState } from '@safe-wordpress/element';
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

export type CssPreviewProps = {
	readonly className?: string;
	readonly previewUrl?: string;
	readonly value: string;
};

export const CssPreview = ( {
	className,
	previewUrl,
	value,
}: CssPreviewProps ): JSX.Element => {
	const instanceId = useInstanceId( CssPreview );
	const [ currentPreviewResolution ] = usePageAttribute(
		'css-preview/activeResolution',
		'desktop'
	);
	const updateIFrame = useIFrameUpdaterWithEffect( instanceId, value );

	return (
		<div className={ classnames( [ className, 'nab-css-preview' ] ) }>
			<iframe
				id={ `nab-css-previewer__iframe-${ instanceId }` }
				className={ classnames( [
					'nab-css-preview__iframe',
					`nab-css-preview__iframe--${ currentPreviewResolution }`,
				] ) }
				title={ _x( 'CSS Preview', 'text', 'nelio-ab-testing' ) }
				onLoad={ updateIFrame }
				src={ previewUrl }
			></iframe>
		</div>
	);
};

// =====
// HOOKS
// =====

type Timeout = ReturnType< typeof setTimeout >;

const useIFrameUpdaterWithEffect = (
	instanceId: string | number,
	value: string
) => {
	const [ timeoutId, setTimeoutId ] = useState< Timeout >();
	const update = () => {
		clearTimeout( timeoutId );
		setTimeoutId(
			setTimeout( () => {
				const iframe = document.getElementById(
					`nab-css-previewer__iframe-${ instanceId }`
				) as HTMLIFrameElement;
				if ( ! iframe?.contentWindow?.postMessage ) {
					return;
				} //end if
				iframe.contentWindow.postMessage( {
					type: 'css-preview',
					value,
				} );
			}, 500 )
		);
	};

	useEffect( update, [ value ] );

	return update;
};
