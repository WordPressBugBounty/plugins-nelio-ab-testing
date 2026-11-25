<?php

namespace Nelio_AB_Testing\Compat\Forms\ContactForm7;

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
	$data['wpcf7_contact_form'] = array(
		'name'   => 'wpcf7_contact_form',
		'label'  => _x( 'Contact Form 7', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Contact Form 7', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}

/**
 * Validates a Contact Form 7 submission action.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_form_submission( $validation, $action ) {
	if ( 'wpcf7_contact_form' !== ( $action['attributes']['formType'] ?? '' ) ) {
		return $validation;
	}

	if ( empty( $action['attributes']['formId'] ) ) {
		$validation['status']   = 'invalid';
		$validation['issues'][] = 'A form ID is required for this form type.';
		return $validation;
	}
	$form_id = absint( $action['attributes']['formId'] );
	if ( 'wpcf7_contact_form' !== get_post_type( $form_id ) ) {
		$validation['status']   = 'invalid';
		$validation['issues'][] = 'The selected Contact Form 7 form does not exist.';
	}
	return $validation;
}
add_filter( 'nab_nab/form-submission_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate_form_submission', 10, 2 );

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
	if ( 'wpcf7_contact_form' !== $action['formType'] ) {
		return;
	}
	add_action(
		'wpcf7_submit',
		function ( $form, $result ) use ( $action, $experiment_id, $goal_index ) {
			/** @var \WPCF7_ContactForm   $form   */
			/** @var array{status:string} $result */

			if ( $action['formId'] !== $form->id() ) {
				return;
			}
			if ( ! in_array( $result['status'], array( 'mail_sent', 'demo_mode' ), true ) ) {
				return;
			}
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		2
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
