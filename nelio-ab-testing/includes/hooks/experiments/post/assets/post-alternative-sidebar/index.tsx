/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { _x } from '@safe-wordpress/i18n';
import { registerPlugin } from '@safe-wordpress/plugins';

/**
 * External dependencies
 */
import { registerCoreExperiments } from '@nab/experiment-library';
import type { Dict, EntityKindName, ExperimentId, PostId } from '@nab/types';

/**
 * Internal dependencies
 */
import { PostAlternativeManagementBox } from '../post-alternative-management-box';

type Settings = {
	readonly experimentId: ExperimentId;
	readonly postBeingEdited: PostId;
	readonly type: EntityKindName;
};

export function initEditPostAlternativeBlockEditorSidebar(
	settings: Settings
): void {
	registerCoreExperiments();

	const { experimentId, postBeingEdited, type } = settings;

	registerPlugin( 'nelio-ab-testing', {
		icon: () => <></>,
		render: () => (
			<AlternativeEditingSidebar
				experimentId={ experimentId }
				postBeingEdited={ postBeingEdited }
				type={ type }
			/>
		),
	} );
} //end initEditPostAlternativeBlockEditorSidebar()

// =======
// HELPERS
// =======

const PluginDocumentSettingPanel =
	window.wp?.editPost?.PluginDocumentSettingPanel;

const AlternativeEditingSidebar = !! PluginDocumentSettingPanel
	? ( { experimentId, postBeingEdited, type }: Settings ) => (
			<PluginDocumentSettingPanel
				className="nab-alternative-editing-sidebar"
				title={ _x( 'Nelio A/B Testing', 'text', 'nelio-ab-testing' ) }
				icon="none"
			>
				<PostAlternativeManagementBox
					experimentId={ experimentId }
					postBeingEdited={ postBeingEdited }
					type={ type }
				/>
			</PluginDocumentSettingPanel>
	  )
	: () => null;

// ==========
// TYPESCRIPT
// ==========

declare global {
	interface Window {
		readonly wp?: {
			readonly editPost?: {
				readonly PluginDocumentSettingPanel: (
					props: Dict
				) => JSX.Element;
			};
		};
	}
}
