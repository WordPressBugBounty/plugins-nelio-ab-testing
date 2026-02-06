<?php

namespace Nelio_AB_Testing\Compat\Forms\SureForms;

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
	$data['sureforms_form'] = array(
		'name'   => 'sureforms_form',
		'label'  => _x( 'SureForms Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'SureForms Form', 'text', 'nelio-ab-testing' ),
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
	if ( 'sureforms_form' !== $action['formType'] ) {
		return;
	}
	add_action(
		'srfm_form_submit',
		function ( $form_data ) use ( $action, $experiment_id, $goal_index ) {
			/** @var array{form_id:int|''} $form_data */

			if ( absint( $form_data['form_id'] ) !== absint( $action['formId'] ) ) {
				return;
			}
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		1
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'sureforms/sureforms.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
