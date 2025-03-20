<?php

namespace Nelio_AB_Testing\Compat\Forminator;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

function add_form_types( $data ) {
	$data['nab_forminator_form'] = array(
		'name'   => 'nab_forminator_form',
		'label'  => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}//end add_form_types()

function get_forminator_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}//end if

	if ( 'nab_forminator_form' !== $post_type ) {
		return $post;
	}//end if

	$form = \Forminator_API::get_form( intval( $post_id ) );

	if ( empty( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: Form ID */
				_x( 'Forminator form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}//end if

	return array(
		'id'          => $post_id,
		'title'       => $form->settings['formName'],
		'excerpt'     => '',
		'imageId'     => 0,
		'imageSrc'    => '',
		'type'        => 'nab_forminator_form',
		'typeLabel'   => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
		'status'      => '',
		'statusLabel' => '',
		'link'        => '',
	);
}//end get_forminator_form()

function search_forminator_forms( $result, $post_type, $term, $per_page, $page ) {
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( 'nab_forminator_form' !== $post_type ) {
		return $result;
	}//end if

	$form_id = absint( $term );

	if ( ! empty( $term ) ) {
		$forms = \Forminator_API::get_forms( array( $form_id ) );
		$forms = array_values(
			array_filter(
				$forms,
				function ( $form ) use ( $term, $form_id ) {
					return $form_id === $form->id || false !== strpos( strtolower( $form->settings['formName'] ), strtolower( $term ) );
				}
			)
		);
	} else {
		$forms = \Forminator_API::get_forms( null, $page, $per_page );
	}//end if

	$published_forms = array_values(
		array_filter(
			$forms,
			function ( $form ) {
				return 'publish' === $form->status;
			}
		)
	);

	$published_forms = array_map(
		function ( $form ) {
			return array(
				'id'          => $form->id,
				'title'       => $form->settings['formName'],
				'excerpt'     => '',
				'imageId'     => 0,
				'imageSrc'    => '',
				'type'        => 'nab_formidable_form',
				'typeLabel'   => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
				'status'      => '',
				'statusLabel' => '',
				'link'        => '',
			);
		},
		$forms
	);

	return array(
		'results'    => $published_forms,
		'pagination' => array(
			'more'  => count( $forms ) === $per_page,
			'pages' => empty( $page ) ? 1 : $page,
		),
	);
}//end search_forminator_forms()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'nab_forminator_form' !== $action['formType'] ) {
		return;
	}//end if

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
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'forminator/forminator.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_forminator_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_forminator_forms', 10, 5 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
