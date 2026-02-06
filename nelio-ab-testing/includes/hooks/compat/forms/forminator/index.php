<?php

namespace Nelio_AB_Testing\Compat\Forms\Forminator;

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
	$data['nab_forminator_form'] = array(
		'name'   => 'nab_forminator_form',
		'label'  => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
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
function get_forminator_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'nab_forminator_form' !== $post_type ) {
		return $post;
	}

	$form = \Forminator_API::get_form( intval( $post_id ) );
	if ( is_wp_error( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: %d: Form ID. */
				_x( 'Forminator form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
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
		'title'        => $form->settings['formName'],
		'type'         => 'nab_forminator_form',
		'typeLabel'    => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
		'extra'        => array(),
	);
}

/**
 * Returns the list of forms matching the search query.
 *
 * @param null|array{results:list<TPost>, pagination: array{more:bool, pages:int}} $result    The result data.
 * @param string                                                                   $post_type The post type.
 * @param string                                                                   $query     The query term.
 * @param int                                                                      $per_page  The number of posts to show per page.
 * @param int                                                                      $page      The number of the current page.
 *
 * @return null|array{results:list<TPost>, pagination: array{more:bool, pages:int}}
 */
function search_forminator_forms( $result, $post_type, $query, $per_page, $page ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'nab_forminator_form' !== $post_type ) {
		return $result;
	}

	if ( empty( $query ) ) {
		$forms = \Forminator_API::get_forms( null, $page, $per_page );
		$forms = ! is_wp_error( $forms ) ? $forms : array();
	} elseif ( absint( $query ) ) {
		$forms = \Forminator_API::get_forms( array( absint( $query ) ) );
		$forms = ! is_wp_error( $forms ) ? $forms : array();
	} else {
		$forms = \Forminator_API::get_forms( null, 0, 100 );
		$forms = ! is_wp_error( $forms ) ? $forms : array();
		$forms = array_values(
			array_filter(
				$forms,
				fn ( $f ) => absint( $query ) === absint( $f->id ) || false !== strpos( strtolower( $f->settings['formName'] ), strtolower( $query ) ),
			)
		);
	}

	$published_forms = array_values( array_filter( $forms, fn( $f ) => 'publish' === $f->status ) );
	$published_forms = array_map(
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
				'title'        => $form->settings['formName'],
				'type'         => 'nab_forminator_form',
				'typeLabel'    => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
				'extra'        => array(),
			);
		},
		$published_forms
	);

	return array(
		'results'    => $published_forms,
		'pagination' => array(
			'more'  => empty( $query ) ? count( $forms ) === $per_page : false,
			'pages' => ! empty( $query ) || empty( $page ) ? 1 : $page,
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
	if ( 'nab_forminator_form' !== $action['formType'] ) {
		return;
	}

	// For non-ajax submission forms.
	add_action(
		'forminator_form_after_handle_submit',
		function ( $form_id, $response ) use ( $action, $experiment_id, $goal_index ) {
			/** @var int                  $form_id  */
			/** @var array{success?:bool} $response */

			if ( empty( $response['success'] ) ) {
				return;
			}
			if ( absint( $form_id ) !== absint( $action['formId'] ) ) {
				return;
			}
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
			}
			if ( absint( $form_id ) !== absint( $action['formId'] ) ) {
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

		if ( ! is_plugin_active( 'forminator/forminator.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_forminator_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_forminator_forms', 10, 5 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
