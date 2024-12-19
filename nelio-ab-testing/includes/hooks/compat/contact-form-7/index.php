<?php

namespace Nelio_AB_Testing\Compat\ContactForm7;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

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
}//end add_form_types()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'wpcf7_contact_form' !== $action['formType'] ) {
		return;
	}//end if
	add_action(
		'wpcf7_submit',
		function ( $form, $result ) use ( $action, $experiment_id, $goal_index ) {
			if ( $action['formId'] !== $form->id() ) {
				return;
			}//end if
			if ( ! in_array( $result['status'], array( 'mail_sent', 'demo_mode' ), true ) ) {
				return;
			}//end if
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		2
	);
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
