/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useInstanceId } from '@safe-wordpress/compose';
import { useSelect } from '@safe-wordpress/data';
import { useEffect } from '@safe-wordpress/element';
import { addQueryArgs, getQueryArg } from '@safe-wordpress/url';
import { _x } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { usePageAttribute, store as NAB_DATA } from '@nab/data';
import { store as NAB_EDITOR } from '@nab/editor';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import { getLocalUrlError } from '@nab/utils';

export type PhpPreviewProps = {
	readonly className?: string;
	readonly alternativeId: AlternativeId;
};

export const PhpPreview = ( {
	className,
	alternativeId,
}: PhpPreviewProps ): JSX.Element => {
	const previewUrl = usePreviewUrl( alternativeId );
	const instanceId = useInstanceId( PhpPreview );
	const [ currentPreviewResolution ] = usePageAttribute(
		'php-preview/activeResolution',
		'desktop'
	);
	useIFrameUpdaterWithEffect( instanceId, previewUrl );

	return (
		<div className={ classnames( [ className, 'nab-php-preview' ] ) }>
			<iframe
				id={ `nab-php-previewer__iframe-${ instanceId }` }
				className={ classnames( [
					'nab-php-preview__iframe',
					`nab-php-preview__iframe--${ currentPreviewResolution }`,
				] ) }
				title={ _x( 'PHP Preview', 'text', 'nelio-ab-testing' ) }
				src={ previewUrl }
			></iframe>
		</div>
	);
};

// =====
// HOOKS
// =====

const useIFrameUpdaterWithEffect = (
	instanceId: string | number,
	url: string | undefined
) => {
	const isSaving = useSelect( ( select ) =>
		select( NAB_EDITOR ).isExperimentBeingSaved()
	);
	const homeUrl = useSelect( ( select ) =>
		select( NAB_DATA ).getPluginSetting( 'homeUrl' )
	);

	useEffect( () => {
		if ( isSaving || ! url ) {
			return;
		} //end if

		const iframe = document.getElementById(
			`nab-php-previewer__iframe-${ instanceId }`
		) as HTMLIFrameElement;
		if ( ! iframe?.contentWindow?.location.href ) {
			return;
		} //end if

		const currentUrl = iframe.contentWindow.location.href;
		const timestamp = getQueryArg( url, 'timestamp' );
		const nabnonce = getQueryArg( url, 'nabnonce' );

		const newUrl = getLocalUrlError( currentUrl, homeUrl )
			? url
			: addQueryArgs( currentUrl, { timestamp, nabnonce } );

		iframe.contentWindow.location.href = newUrl;
	}, [ isSaving, url, homeUrl ] );
};

const usePreviewUrl = ( alternativeId: AlternativeId ) =>
	useSelect( ( select ) => {
		const { getAlternative } = select( NAB_EDITOR );
		const alternative = getAlternative( alternativeId );
		return alternative?.links.preview;
	} );
