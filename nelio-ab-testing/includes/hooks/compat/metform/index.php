<?php

namespace Nelio_AB_Testing\Compat\MetForm;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

function add_form_types( $data ) {
	$data['metform-form'] = array(
		'name'   => 'metform-form',
		'label'  => _x( 'MetForm', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'MetForm', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}//end add_form_types()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'metform-form' !== $action['formType'] ) {
		return;
	}//end if
	add_action(
		'metform_after_store_form_data',
		function ( $form_id ) use ( $action, $experiment_id, $goal_index ) {
			if ( absint( $form_id ) !== $action['formId'] ) {
				return;
			}//end if
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		1
	);
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}//end if

		if ( ! is_plugin_active( 'metform/metform.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
