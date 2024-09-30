/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { Button, Dashicon } from '@safe-wordpress/components';
import { _x } from '@safe-wordpress/i18n';
import { useSelect } from '@safe-wordpress/data';

/**
 * External dependencies
 */
import { store as NAB_EDITOR } from '@nab/editor';

export type SaveButtonProps = {
	readonly isSaving: boolean;
	readonly save: () => void;
};

export const SaveButton = ( {
	isSaving,
	save,
}: SaveButtonProps ): JSX.Element => {
	const experimentUrl = useExperimentUrl();

	return (
		<div className="nab-javascript-editor-sidebar__actions">
			<div className="nab-javascript-editor-sidebar__back-to-experiment">
				<a
					className="nab-javascript-editor-sidebar__back-to-experiment-link"
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

			<div className="nab-javascript-editor-sidebar__save-info">
				{ isSaving && (
					<span className="nab-javascript-editor-sidebar__saving-label">
						<Dashicon icon="cloud" />
						{ _x( 'Saving…', 'text', 'nelio-ab-testing' ) }
					</span>
				) }
				<Button
					variant="primary"
					disabled={ isSaving }
					onClick={ save }
				>
					{ _x( 'Save and Preview', 'command', 'nelio-ab-testing' ) }
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
