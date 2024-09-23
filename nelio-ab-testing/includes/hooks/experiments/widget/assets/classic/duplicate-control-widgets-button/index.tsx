/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import apiFetch from '@safe-wordpress/api-fetch';
import { Button } from '@safe-wordpress/components';
import { useEffect } from '@safe-wordpress/element';
import { _x } from '@safe-wordpress/i18n';
import { addQueryArgs } from '@safe-wordpress/url';

/**
 * External dependencies
 */
import { ConfirmationDialog } from '@nab/components';
import { usePageAttribute } from '@nab/data';
import type { AlternativeId, ExperimentId } from '@nab/types';

export type DuplicateControlWidgetsButtonProps = {
	readonly experiment: ExperimentId;
	readonly alternative: AlternativeId;
};

export const DuplicateControlWidgetsButton = ( {
	experiment,
	alternative,
}: DuplicateControlWidgetsButtonProps ): JSX.Element => {
	const [ isConfirmVisible, showConfirm ] = useConfirmationDialog();
	const [ isDuplicating, duplicateWidgets ] = useWidgetDuplicator(
		experiment,
		alternative
	);

	return (
		<span>
			<Button
				className="page-title-action"
				onClick={ () => showConfirm( true ) }
				style={ { height: 'auto' } }
			>
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
				confirmLabel={
					isDuplicating
						? _x(
								'Duplicating…',
								'text (widgets)',
								'nelio-ab-testing'
						  )
						: _x( 'Duplicate', 'command', 'nelio-ab-testing' )
				}
				isConfirmEnabled={ ! isDuplicating }
				isCancelEnabled={ ! isDuplicating }
				isOpen={ isConfirmVisible }
				onCancel={ () => showConfirm( false ) }
				onConfirm={ duplicateWidgets }
			/>
		</span>
	);
};

// =====
// HOOKS
// =====

const useConfirmationDialog = () =>
	usePageAttribute(
		'widgets/isConfirmationDialogForWidgetDuplicationVisible',
		false
	);

const useWidgetDuplicator = (
	experiment: ExperimentId,
	alternative: AlternativeId
) => {
	const [ isDuplicating, markAsDuplicating ] = usePageAttribute(
		'widgets/isDuplicatingWidgets',
		false
	);

	useEffect( () => {
		if ( ! isDuplicating ) {
			return;
		} //end if
		void apiFetch( {
			path: addQueryArgs( '/nab/v1/widget/duplicate-control', {
				experiment,
				alternative,
			} ),
			method: 'PUT',
		} ).finally( () => {
			window.location.reload();
		} );
	}, [ isDuplicating ] );

	return [ isDuplicating, () => markAsDuplicating( true ) ] as const;
};
