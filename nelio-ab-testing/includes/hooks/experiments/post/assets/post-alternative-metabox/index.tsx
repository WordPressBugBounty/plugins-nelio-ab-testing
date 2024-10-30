/**
 * WordPress dependencies
 */
import * as React from '@safe-wordpress/element';
import { render } from '@safe-wordpress/element';

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

export function initEditPostAlternativeMetabox( settings: Settings ): void {
	registerCoreExperiments();

	const { experimentId, postBeingEdited, type } = settings;

	const metabox = document.getElementById(
		'nelioab_edit_post_alternative_box'
	);
	const content = metabox?.getElementsByClassName( 'inside' )[ 0 ];
	if ( ! content ) {
		return;
	} //end if

	render(
		<PostAlternativeManagementBox
			experimentId={ experimentId }
			postBeingEdited={ postBeingEdited }
			type={ type }
		/>,
		content
	);
} //end initEditPostAlternativeMetabox()
