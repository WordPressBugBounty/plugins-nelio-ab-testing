<?php

namespace Nelio_AB_Testing\Compat\FormidableForms;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

function add_form_types( $data ) {
	$data['nab_formidable_form'] = array(
		'name'   => 'nab_formidable_form',
		'label'  => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}//end add_form_types()

function get_formidable_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}//end if

	if ( 'nab_formidable_form' !== $post_type ) {
		return $post;
	}//end if

	$form = \FrmForm::getOne( intval( $post_id ) );

	if ( empty( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: Form ID */
				_x( 'Formidable form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}//end if

	return array(
		'id'          => $post_id,
		'title'       => $form->name,
		'excerpt'     => '',
		'imageId'     => 0,
		'imageSrc'    => '',
		'type'        => 'nab_formidable_form',
		'typeLabel'   => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
		'status'      => '',
		'statusLabel' => '',
		'link'        => '',
	);
}//end get_formidable_form()

function search_formidable_forms( $result, $post_type, $term ) {
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( 'nab_formidable_form' !== $post_type ) {
		return $result;
	}//end if

	$forms   = \FrmForm::getAll();
	$form_id = absint( $term );

	if ( ! empty( $term ) ) {
		$forms = array_values(
			array_filter(
				$forms,
				function ( $form ) use ( $term, $form_id ) {
					return $form_id === $form->id || false !== strpos( strtolower( $form->name ), strtolower( $term ) );
				}
			)
		);
	}//end if

	$forms = array_map(
		function ( $form ) {
			return array(
				'id'          => $form->id,
				'title'       => $form->name,
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
		'results'    => $forms,
		'pagination' => array(
			'more'  => false,
			'pages' => 1,
		),
	);
}//end search_formidable_forms()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'nab_formidable_form' !== $action['formType'] ) {
		return;
	}//end if
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
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'formidable/formidable.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_formidable_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_formidable_forms', 10, 3 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
