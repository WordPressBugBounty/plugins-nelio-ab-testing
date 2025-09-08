<?php

namespace Nelio_AB_Testing\Compat\MW_WP_Form;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function class_exists;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

function add_form_types( $data ) {
	$data['mw-wp-form'] = array(
		'name'   => 'mw-wp-form',
		'label'  => _x( 'MW WP Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'MW WP Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}//end add_form_types()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'mw-wp-form' !== $action['formType'] ) {
		return;
	}//end if

	if ( ! class_exists( 'MWF_Functions' ) ) {
		return;
	}//end if

	$form_key = \MWF_Functions::get_form_key_from_form_id( $action['formId'] );
	add_action(
		'mwform_after_send_' . $form_key,
		function () use ( $experiment_id, $goal_index ) {
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

		if ( ! is_plugin_active( 'mw-wp-form/mw-wp-form.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
