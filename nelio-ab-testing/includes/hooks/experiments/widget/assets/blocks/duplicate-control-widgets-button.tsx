/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import apiFetch from '@safe-wordpress/api-fetch';
import { Button } from '@safe-wordpress/components';
import { useState } from '@safe-wordpress/element';
import { _x } from '@safe-wordpress/i18n';
import { addQueryArgs } from '@safe-wordpress/url';

/**
 * External dependencies
 */
import { ConfirmationDialog } from '@nab/components';
import type { AlternativeId, ExperimentId } from '@nab/types';

export type DuplicateControlWidgetsButtonProps = {
	readonly experiment: ExperimentId;
	readonly alternative: AlternativeId;
};

export const DuplicateControlWidgetsButton = ( {
	experiment,
	alternative,
}: DuplicateControlWidgetsButtonProps ): JSX.Element => {
	const [ isWorking, markAsWorking ] = useState( false );
	const [ isDialogVisible, openDialog ] = useState( false );

	const duplicateWidgets = () => {
		if ( isWorking ) {
			return;
		} //end if
		markAsWorking( true );
		void apiFetch( {
			path: addQueryArgs( '/nab/v1/widget/duplicate-control', {
				experiment,
				alternative,
			} ),
			method: 'PUT',
		} ).finally( () => window.location.reload() );
	};

	const confirmationLabel = isWorking
		? _x( 'Duplicating…', 'text (widgets)', 'nelio-ab-testing' )
		: _x( 'Duplicate', 'command', 'nelio-ab-testing' );
	return (
		<>
			<Button variant="secondary" onClick={ () => openDialog( true ) }>
				{ _x(
					'Duplicate Control Widgets',
					'command',
					'nelio-ab-testing'
				) }
			</Button>
			<ConfirmationDialog
				title={ _x(
					'Duplicate Control Widgets?',
					'title',
					'nelio-ab-testing'
				) }
				text={ _x(
					'This will overwrite any widgets you may have in this variant with those included in your theme. Are you sure you want to continue?',
					'user',
					'nelio-ab-testing'
				) }
				confirmLabel={ confirmationLabel }
				isConfirmEnabled={ ! isWorking }
				isCancelEnabled={ ! isWorking }
				isDismissible={ ! isWorking }
				isOpen={ isDialogVisible }
				onCancel={ () => openDialog( false ) }
				onConfirm={ duplicateWidgets }
			/>
		</>
	);
};
