<?php

namespace Nelio_AB_Testing\Compat\Forms\WPForms;

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
	$data['wpforms'] = array(
		'name'   => 'wpforms',
		'label'  => _x( 'WPForm', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'WPForm', 'text', 'nelio-ab-testing' ),
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
	if ( 'wpforms' !== $action['formType'] ) {
		return;
	}
	add_action(
		'wpforms_process_complete',
		function ( $fields, $entry, $form_data ) use ( $action, $experiment_id, $goal_index ) {
			/** @var TIgnore             $fields    */
			/** @var TIgnore             $entry     */
			/** @var array{id:mixed} $form_data */

			if ( absint( $form_data['id'] ) !== absint( $action['formId'] ) ) {
				return;
			}
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		3
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'wpforms-lite/wpforms.php' ) && ! is_plugin_active( 'wpforms/wpforms.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
