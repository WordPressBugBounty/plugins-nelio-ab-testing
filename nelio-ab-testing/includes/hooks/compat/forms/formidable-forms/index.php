<?php

namespace Nelio_AB_Testing\Compat\Forms\FormidableForms;

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
	$data['nab_formidable_form'] = array(
		'name'   => 'nab_formidable_form',
		'label'  => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}

/**
 * Callback to return the appropriate form.
 *
 * @param null|TPost|\WP_Post|\WP_Error $post      The post to filter.
 * @param int|string                    $post_id   The id of the post.
 * @param string                        $post_type The post type.
 *
 * @return null|TPost|\WP_Post|\WP_Error
 */
function get_formidable_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'nab_formidable_form' !== $post_type ) {
		return $post;
	}

	$form = \FrmForm::getOne( intval( $post_id ) );

	if ( empty( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: %d: Form ID. */
				_x( 'Formidable form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}

	return array(
		'id'           => absint( $post_id ),
		'author'       => 0,
		'authorName'   => '',
		'date'         => false,
		'excerpt'      => '',
		'imageId'      => 0,
		'imageSrc'     => '',
		'link'         => '',
		'status'       => '',
		'statusLabel'  => '',
		'thumbnailSrc' => '',
		'title'        => $form->name,
		'type'         => 'nab_formidable_form',
		'typeLabel'    => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
		'extra'        => array(),
	);
}

/**
 * Returns the list of forms matching the search query.
 *
 * @param null|array{results:list<TPost>, pagination: array{more:bool, pages:int}} $result    The result data.
 * @param string                                                                   $post_type The post type.
 * @param string                                                                   $query     The query term.
 *
 * @return null|array{results:list<TPost>, pagination: array{more:bool, pages:int}}
 */
function search_formidable_forms( $result, $post_type, $query ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'nab_formidable_form' !== $post_type ) {
		return $result;
	}

	$forms = \FrmForm::getAll();
	if ( ! empty( $query ) ) {
		$forms = array_values(
			array_filter(
				$forms,
				fn ( $f ) => absint( $query ) === $f->id || false !== strpos( strtolower( $f->name ), strtolower( $query ) )
			)
		);
	}

	$forms = array_map(
		function ( $form ) {
			return array(
				'id'           => absint( $form->id ),
				'author'       => 0,
				'authorName'   => '',
				'date'         => false,
				'excerpt'      => '',
				'imageId'      => 0,
				'imageSrc'     => '',
				'link'         => '',
				'status'       => '',
				'statusLabel'  => '',
				'thumbnailSrc' => '',
				'title'        => $form->name,
				'type'         => 'nab_formidable_form',
				'typeLabel'    => _x( 'Formidable Form', 'text', 'nelio-ab-testing' ),
				'extra'        => array(),
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
	if ( 'nab_formidable_form' !== $action['formType'] ) {
		return;
	}
	add_action(
		'frm_after_create_entry',
		function ( $entry_id, $form_id ) use ( $action, $experiment_id, $goal_index ) {
			/** @var TIgnore $entry_id */
			/** @var int     $form_id  */

			if ( absint( $form_id ) !== absint( $action['formId'] ) ) {
				return;
			}
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		30,
		2
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'formidable/formidable.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_formidable_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_formidable_forms', 10, 3 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
