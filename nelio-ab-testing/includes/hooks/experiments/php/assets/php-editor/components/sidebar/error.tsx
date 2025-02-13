/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { useSelect } from '@safe-wordpress/data';
import { _x, sprintf } from '@safe-wordpress/i18n';

/**
 * External dependencies
 */
import { store as NAB_EDITOR } from '@nab/editor';
import type { AlternativeId } from '@nab/types';

/**
 * Internal dependencies
 */
import './style.scss';
import type { AlternativeAttributes } from '../../../../../../../../packages/experiment-library/php/types';

export type ErrorProps = {
	readonly alternativeId: AlternativeId;
};

export const Error = ( { alternativeId }: ErrorProps ): JSX.Element | null => {
	const { type, message } = useErrorMessage( alternativeId );
	switch ( type ) {
		case 'error':
			return (
				<div className="nab-php-editor-sidebar__error">
					{ sprintf(
						/* translators: error message */
						_x(
							'There’s a problem with your code: %s',
							'text',
							'nelio-ab-testing'
						),
						message
					) }
				</div>
			);
		case 'warning':
			return (
				<div className="nab-php-editor-sidebar__error">
					{ sprintf(
						/* translators: warning message */
						_x(
							'There might be a problem with your code: %s',
							'text',
							'nelio-ab-testing'
						),
						message
					) }
				</div>
			);

		case 'none':
			return null;
	} //end switch
};

// =====
// HOOKS
// =====

const useErrorMessage = ( alternativeId: AlternativeId ) => {
	const alternative = useSelect( ( select ) =>
		select( NAB_EDITOR ).getAlternative< AlternativeAttributes >(
			alternativeId
		)
	);

	if ( alternative?.attributes?.errorMessage ) {
		return {
			type: 'error' as const,
			message: alternative.attributes.errorMessage,
		};
	} //end if

	if ( alternative?.attributes?.warningMessage ) {
		return {
			type: 'warning' as const,
			message: alternative.attributes.warningMessage,
		};
	} //end if

	return { type: 'none' as const, message: '' };
};
