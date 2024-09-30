/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { Button, Dashicon } from '@safe-wordpress/components';
import { _x } from '@safe-wordpress/i18n';
import { useDispatch, useSelect } from '@safe-wordpress/data';

/**
 * External dependencies
 */
import { store as NAB_EDITOR } from '@nab/editor';

export const SaveButton = (): JSX.Element => {
	const experimentUrl = useExperimentUrl();
	const [ isSaving, save ] = useSave();

	return (
		<div className="nab-css-editor-sidebar__actions">
			<div className="nab-css-editor-sidebar__back-to-experiment">
				<a
					className="nab-css-editor-sidebar__back-to-experiment-link"
					href={ experimentUrl }
					title={ _x(
						'Back to test…',
						'command',
						'nelio-ab-testing'
					) }
				>
					<Dashicon icon="arrow-left-alt2" />
				</a>
			</div>

			<div className="nab-css-editor-sidebar__save-info">
				{ isSaving && (
					<span className="nab-css-editor-sidebar__saving-label">
						<Dashicon icon="cloud" />
						{ _x( 'Saving…', 'text', 'nelio-ab-testing' ) }
					</span>
				) }
				<Button
					variant="primary"
					disabled={ isSaving }
					onClick={ () => void save() }
				>
					{ _x( 'Save', 'command', 'nelio-ab-testing' ) }
				</Button>
			</div>
		</div>
	);
};

// =====
// HOOKS
// =====

const useExperimentUrl = () =>
	useSelect(
		( select ) =>
			select( NAB_EDITOR ).getExperimentAttribute( 'links' )?.edit
	);

const useSave = () => {
	const isSaving = useSelect( ( select ) =>
		select( NAB_EDITOR ).isExperimentBeingSaved()
	);
	const { saveExperiment } = useDispatch( NAB_EDITOR );
	return [ isSaving, saveExperiment ] as const;
};
