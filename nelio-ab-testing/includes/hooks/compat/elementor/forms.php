<?php

namespace Nelio_AB_Testing\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

function add_form_types( $data ) {
	$data['nab_elementor_form'] = array(
		'name'   => 'nab_elementor_form',
		'label'  => _x( 'Elementor Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Elementor Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}//end add_form_types()

function get_elementor_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}//end if

	if ( 'nab_elementor_form' !== $post_type ) {
		return $post;
	}//end if

	return new \WP_Error(
		'not-found',
		_x( 'Elementor forms are not exposed through this endpoint.', 'text', 'nelio-ab-testing' )
	);
}//end get_elementor_form()

function get_elementor_forms( $result, $post_type ) {
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( 'nab_elementor_form' !== $post_type ) {
		return $result;
	}//end if

	return array();
}//end get_elementor_forms()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'nab_elementor_form' !== $action['formType'] ) {
		return;
	}//end if
	add_filter(
		'elementor_pro/forms/new_record',
		function ( $record ) use ( $action, $experiment_id, $goal_index ) {
			$form_name = $record->get_form_settings( 'form_name' );
			if ( $action['formName'] !== $form_name ) {
				return $record;
			}//end if
			maybe_sync_event_submission( $experiment_id, $goal_index );
			return $record;
		}
	);
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_elementor_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\get_elementor_forms', 10, 2 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
