<?php

namespace Nelio_AB_Testing\Compat\Forms\WSForm;

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
	$data['nab_ws_form'] = array(
		'name'   => 'nab_ws_form',
		'label'  => _x( 'WS Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'WS Form', 'text', 'nelio-ab-testing' ),
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
function get_ws_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'nab_ws_form' !== $post_type ) {
		return $post;
	}

	try {
		$form = wsf_form_get_object( absint( $post_id ), false, false );
	} catch ( \Exception $e ) {
		$form = null;
	}

	if ( empty( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: %d: Form ID. */
				_x( 'WS Form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
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
		'title'        => $form->label,
		'type'         => 'nab_ws_form',
		'typeLabel'    => _x( 'WS Form', 'text', 'nelio-ab-testing' ),
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
function search_ws_forms( $result, $post_type, $query ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'nab_ws_form' !== $post_type ) {
		return $result;
	}

	$forms = wsf_form_get_all( true );
	$forms = is_array( $forms ) ? $forms : array();

	if ( ! empty( $query ) ) {
		$forms = array_values(
			array_filter(
				$forms,
				fn( $f ) => absint( $query ) === absint( $f['id'] ) || false !== strpos( strtolower( $f['label'] ), strtolower( $query ) ),
			)
		);
	}

	$forms = array_map(
		function ( $form ) {
			return array(
				'id'           => absint( $form['id'] ),
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
				'title'        => $form['label'],
				'type'         => 'nab_ws_form',
				'typeLabel'    => _x( 'WS Form', 'text', 'nelio-ab-testing' ),
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
	if ( 'nab_ws_form' !== $action['formType'] ) {
		return;
	}
	// Inspect the final response: completion hooks also run for failed validation.
	add_filter(
		'wsf_api_submit_response_data',
		function ( $response, $submit ) use ( $action, $experiment_id, $goal_index ) {
			/** @var array{error?:bool,error_validation?:bool,data?:array{errors?:array<mixed>}} $response */
			/** @var object{form_id:int,post_mode:string,status:string,preview:bool,actions_stopped?:bool,form_object:object{meta?:object{spam_threshold?:int}}} $submit */

			if ( absint( $submit->form_id ) !== absint( $action['formId'] ) || 'submit' !== $submit->post_mode ) {
				return $response;
			}
			if ( ! empty( $response['error'] ) || ! empty( $response['error_validation'] ) || ! empty( $response['data']['errors'] ) ) {
				return $response;
			}
			if ( ! empty( $submit->preview ) || 'spam' === $submit->status || ! empty( $submit->actions_stopped ) ) {
				return $response;
			}
			/** @var object{spam_threshold?:int|string}|null $meta */
			$meta           = $submit->form_object->meta ?? null;
			$spam_threshold = absint( $meta->spam_threshold ?? 50 );
			if ( \WS_Form_Action::$spam_level >= $spam_threshold ) {
				return $response;
			}

			maybe_sync_event_submission( $experiment_id, $goal_index );
			return $response;
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

		if ( ! is_plugin_active( 'ws-form/ws-form.php' ) && ! is_plugin_active( 'ws-form-pro/ws-form.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_ws_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_ws_forms', 10, 3 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
