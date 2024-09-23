<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;
use function nab_track_conversion;

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {

	if ( 'nelio_form' === $action['formType'] ) {
		add_action(
			'nelio_forms_process_complete',
			function( $fields, $form, $entry ) use ( $action, $experiment_id, $goal_index ) {
				if ( absint( $form['id'] ) !== $action['formId'] ) {
					return;
				}//end if

				maybe_sync_event_submission( $experiment_id, $goal_index );
			},
			10,
			3
		);
	}//end if

	if ( 'nab_gravity_form' === $action['formType'] ) {
		add_action(
			'gform_after_submission',
			function ( $entry, $form ) use ( $action, $experiment_id, $goal_index ) {
				if ( absint( $form['id'] ) !== $action['formId'] ) {
					return;
				}//end if
				maybe_sync_event_submission( $experiment_id, $goal_index );
			},
			10,
			2
		);
	}//end if

	if ( 'wpcf7_contact_form' === $action['formType'] ) {
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
	}//end if

	if ( 'nab_ninja_form' === $action['formType'] ) {
		add_action(
			'ninja_forms_after_submission',
			function ( $form ) use ( $action, $experiment_id, $goal_index ) {
				if ( absint( $form['form_id'] ) !== $action['formId'] ) {
					return;
				}//end if
				maybe_sync_event_submission( $experiment_id, $goal_index );
			}
		);
	}//end if

	if ( 'nab_formidable_form' === $action['formType'] ) {
		add_action(
			'frm_after_create_entry',
			function ( $entry_id, $form_id ) use ( $action, $experiment_id, $goal_index ) {
				if ( absint( $form_id ) !== $action['formId'] ) {
					return;
				}//end if
				maybe_sync_event_submission( $experiment_id, $goal_index );
			},
			30,
			2
		);
	}//end if

	if ( 'wpforms' === $action['formType'] ) {
		add_action(
			'wpforms_process_complete',
			function ( $fields, $entry, $form_data ) use ( $action, $experiment_id, $goal_index ) {
				if ( absint( $form_data['id'] ) !== $action['formId'] ) {
					return;
				}//end if
				maybe_sync_event_submission( $experiment_id, $goal_index );
			},
			10,
			3
		);
	}//end if

	if ( 'nab_elementor_form' === $action['formType'] ) {
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
	}//end if

	if ( 'nab_forminator_form' === $action['formType'] ) {
		// For non-ajax submission forms.
		add_action(
			'forminator_form_after_handle_submit',
			function ( $form_id, $response ) use ( $action, $experiment_id, $goal_index ) {
				if ( empty( $response ) || ! is_array( $response ) || ! $response['success'] ) {
					return;
				}//end if
				if ( absint( $form_id ) !== $action['formId'] ) {
					return;
				}//end if
				maybe_sync_event_submission( $experiment_id, $goal_index );
			},
			10,
			2
		);
		// For ajax submission forms.
		add_action(
			'forminator_form_after_save_entry',
			function ( $form_id, $response ) use ( $action, $experiment_id, $goal_index ) {
				if ( empty( $response ) || ! is_array( $response ) || ! $response['success'] ) {
					return;
				}//end if
				if ( absint( $form_id ) !== $action['formId'] ) {
					return;
				}//end if
				maybe_sync_event_submission( $experiment_id, $goal_index );
			},
			10,
			2
		);
	}//end if
}//end add_hooks_for_tracking()
add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );

function maybe_sync_event_submission( $experiment_id, $goal_index ) {

	$experiments = nab_get_experiments_with_page_view_from_request();
	if ( ! isset( $experiments[ $experiment_id ] ) ) {
		return;
	}//end if

	$all_views      = nab_get_experiments_with_page_view_from_request();
	$all_unique_ids = nab_get_unique_views_from_request();
	$all_segments   = nab_get_segments_from_request();

	$alternative = nab_array_get( $all_views, $experiment_id, false );
	$unique_id   = nab_array_get( $all_unique_ids, $experiment_id, false );
	$segments    = nab_array_get( $all_segments, $experiment_id, array( 0 ) );

	nab_track_conversion(
		$experiment_id,
		$goal_index,
		$alternative,
		array(
			'unique_id' => $unique_id,
			'segments'  => $segments,
		)
	);

}//end maybe_sync_event_submission()
