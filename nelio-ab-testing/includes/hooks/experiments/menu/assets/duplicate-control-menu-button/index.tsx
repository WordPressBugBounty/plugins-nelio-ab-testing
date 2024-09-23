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

export type DuplicateControlMenuButtonProps = {
	readonly experiment: ExperimentId;
	readonly alternative: AlternativeId;
};

export const DuplicateControlMenuButton = ( {
	experiment,
	alternative,
}: DuplicateControlMenuButtonProps ): JSX.Element => {
	const [ isDuplicating, duplicateMenu ] = useMenuDuplicator(
		experiment,
		alternative
	);
	const [ isConfirmVisible, showConfirm ] = useConfirmationDialog();

	return (
		<>
			<Button
				className="page-title-action"
				onClick={ () => showConfirm( true ) }
				style={ { height: 'auto' } }
			>
				{ _x(
					'Duplicate Control Menu',
					'command',
					'nelio-ab-testing'
				) }
			</Button>
			<ConfirmationDialog
				title={ _x(
					'Duplicate Control Menu?',
					'title',
					'nelio-ab-testing'
				) }
				text={ _x(
					'This will overwrite the items in your menu variant using those included in the control menu. Are you sure you want to continue?',
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
				onConfirm={ duplicateMenu }
			/>
		</>
	);
};

// =====
// HOOKS
// =====

const useConfirmationDialog = () =>
	usePageAttribute(
		'menus/isConfirmationDialogForMenuDuplicationVisible',
		false
	);

const useMenuDuplicator = (
	experiment: ExperimentId,
	alternative: AlternativeId
) => {
	const [ isDuplicating, markAsDuplicating ] = usePageAttribute(
		'menus/isDuplicatingMenu',
		false
	);

	useEffect( () => {
		if ( ! isDuplicating ) {
			return;
		} //end if
		void apiFetch( {
			path: addQueryArgs( '/nab/v1/menu/duplicate-control', {
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
