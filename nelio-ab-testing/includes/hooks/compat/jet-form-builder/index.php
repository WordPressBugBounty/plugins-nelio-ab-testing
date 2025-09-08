<?php

namespace Nelio_AB_Testing\Compat\JetFormBuilder;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

function add_form_types( $data ) {
	$data['jet-form-builder'] = array(
		'name'   => 'jet-form-builder',
		'label'  => _x( 'JetFormBuilder Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'JetFormBuilder Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}//end add_form_types()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'jet-form-builder' !== $action['formType'] ) {
		return;
	}//end if
	add_action(
		'jet-form-builder/form-handler/after-send',
		function ( $form ) use ( $action, $experiment_id, $goal_index ) {
			if ( ! $form->is_success ) {
				return;
			}//end if

			$form_id = intval( $form->form_id );

			if ( absint( $form_id ) !== $action['formId'] ) {
				return;
			}//end if

			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
	);
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}//end if

		if ( ! is_plugin_active( 'jetformbuilder/jet-form-builder.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
