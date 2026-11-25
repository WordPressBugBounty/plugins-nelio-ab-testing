<?php

namespace Nelio_AB_Testing\Compat\Elementor;

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
	$data['nab_elementor_form'] = array(
		'name'   => 'nab_elementor_form',
		'label'  => _x( 'Elementor Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Elementor Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}

/**
 * Validates an Elementor form submission action.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_form_submission( $validation, $action ) {
	if ( 'nab_elementor_form' !== ( $action['attributes']['formType'] ?? '' ) ) {
		return $validation;
	}

	// NOTE. Elementor forms are identified by runtime names and are not exposed for lookup here.
	if ( empty( $action['attributes']['formName'] ) ) {
		$validation['status']   = 'invalid';
		$validation['issues'][] = 'A form name is required for this form type.';
		return $validation;
	}

	return $validation;
}
add_filter( 'nab_nab/form-submission_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate_form_submission', 10, 2 );

/**
 * Callback to return the appropriate form.
 *
 * @param null|TPost|\WP_Post|\WP_Error $post      The post to filter.
 * @param int|string                    $post_id   The id of the post.
 * @param string                        $post_type The post type.
 *
 * @return null|TPost|\WP_Post|\WP_Error
 */
function get_elementor_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'nab_elementor_form' !== $post_type ) {
		return $post;
	}

	return new \WP_Error(
		'not-found',
		_x( 'Elementor forms are not exposed through this endpoint.', 'text', 'nelio-ab-testing' )
	);
}

/**
 * Returns the list of forms matching the search query.
 *
 * @param null|array{results:list<TPost>, pagination: array{more:bool, pages:int}} $result    The result data.
 * @param string                                                                   $post_type The post type.
 *
 * @return null|array{results:list<TPost>, pagination: array{more:bool, pages:int}}
 */
function get_elementor_forms( $result, $post_type ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'nab_elementor_form' !== $post_type ) {
		return $result;
	}

	return array(
		'results'    => array(),
		'pagination' => array(
			'more'  => false,
			'pages' => 0,
		),
	);
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
	if ( 'nab_elementor_form' !== $action['formType'] ) {
		return;
	}
	add_filter(
		'elementor_pro/forms/new_record',
		function ( $record ) use ( $action, $experiment_id, $goal_index ) {
			/** @var \ElementorPro\Modules\Forms\Classes\Form_Record $record */

			$form_name = $record->get_form_settings( 'form_name' );
			if ( ! isset( $action['formName'] ) || $action['formName'] !== $form_name ) {
				return $record;
			}
			maybe_sync_event_submission( $experiment_id, $goal_index );
			return $record;
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_elementor_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\get_elementor_forms', 10, 2 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
