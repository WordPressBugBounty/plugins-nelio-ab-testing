/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { PluginDocumentSettingPanel } from '@safe-wordpress/edit-post';
import { _x } from '@safe-wordpress/i18n';
import { registerPlugin } from '@safe-wordpress/plugins';

/**
 * External dependencies
 */
import { registerCoreExperiments } from '@nab/experiment-library';
import type { EntityKindName, ExperimentId, PostId } from '@nab/types';

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

const AlternativeEditingSidebar = !! PluginDocumentSettingPanel
	? ( { experimentId, postBeingEdited, type }: Settings ) => (
			<PluginDocumentSettingPanel
				className="nab-alternative-editing-sidebar"
				title={ _x( 'Nelio A/B Testing', 'text', 'nelio-ab-testing' ) }
			>
				<PostAlternativeManagementBox
					experimentId={ experimentId }
					postBeingEdited={ postBeingEdited }
					type={ type }
				/>
			</PluginDocumentSettingPanel>
	  )
	: () => null;
