<?php

namespace Nelio_AB_Testing\Compat\Forms\JetFormBuilder;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

/**
 * Adds a new form type.
 *
 * @param array<string,TPost_Type> $data Post types.
 *
 * @return array<string,TPost_Type>
 */
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
}

/**
 * Adds hooks for tracking a form submission.
 *
 * @param TForm_Submission_Attributes $action        Action.
 * @param int                         $experiment_id Experiment ID.
 * @param int                         $goal_index    Goal index.
 *
 * @return void
 */
function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'jet-form-builder' !== $action['formType'] ) {
		return;
	}
	add_action(
		'jet-form-builder/form-handler/after-send',
		function ( $form ) use ( $action, $experiment_id, $goal_index ) {
			/** @var \Jet_Form_Builder\Form_Handler $form */

			if ( ! $form->is_success ) {
				return;
			}

			if ( absint( $form->form_id ) !== absint( $action['formId'] ) ) {
				return;
			}

			maybe_sync_event_submission( $experiment_id, $goal_index );
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'jetformbuilder/jet-form-builder.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
