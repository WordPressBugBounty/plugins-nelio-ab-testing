<?php

namespace Nelio_AB_Testing\Compat\Forms\FluentForms;

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
	$data['nab_fluent_form'] = array(
		'name'   => 'nab_fluent_form',
		'label'  => _x( 'Fluent Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Fluent Form', 'text', 'nelio-ab-testing' ),
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
function get_fluent_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'nab_fluent_form' !== $post_type ) {
		return $post;
	}

	$form = \FluentForm\App\Models\Form::where( 'id', $post_id )->first();

	if ( empty( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: %d: Form ID. */
				_x( 'Fluent form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}

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
		'title'        => $form['title'],
		'type'         => 'nab_fluent_form',
		'typeLabel'    => _x( 'Fluent Form', 'text', 'nelio-ab-testing' ),
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
function search_fluent_forms( $result, $post_type, $query, $per_page, $page ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'nab_fluent_form' !== $post_type ) {
		return $result;
	}

	$forms = \FluentForm\App\Models\Form::select( array( 'id', 'title', 'status' ) )
		->where( 'status', 'published' )
		->orderBy( 'title', 'DESC' )
		->get();
	$forms = $forms->getDictionary();
	$forms = array_map( fn( $f ) => $f->getAttributes(), $forms );
	/** @var list<array{id:numeric, title:string, status:string}> */
	$forms = array_values( $forms );

	$query = strtolower( trim( $query ) );
	if ( ! empty( $query ) ) {
		$form_id = absint( $query );
		$forms   = array_values(
			array_filter(
				$forms,
				fn( $form ) => (
					absint( $form['id'] ) === absint( $form_id ) ||
					false !== strpos( strtolower( $form['title'] ), $query )
				)
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
				'title'        => $form['title'],
				'type'         => 'nab_fluent_form',
				'typeLabel'    => _x( 'Fluent Form', 'text', 'nelio-ab-testing' ),
				'extra'        => array(),
			);
		},
		$forms
	);

	return array(
		'results'    => $forms,
		'pagination' => array(
			'more'  => count( $forms ) === $per_page,
			'pages' => empty( $page ) ? 1 : $page,
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
	if ( 'nab_fluent_form' !== $action['formType'] ) {
		return;
	}
	add_action(
		'fluentform/notify_on_form_submit',
		function ( $entry_id, $form_data, $form ) use ( $action, $experiment_id, $goal_index ) {
			/** @var TIgnore                     $entry_id  */
			/** @var TIgnore                     $form_data */
			/** @var \FluentForm\App\Models\Form $form      */

			if ( absint( $form->getAttributes()['id'] ) !== absint( $action['formId'] ) ) {
				return;
			}
			$args = array();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['data'] ) && is_string( $_REQUEST['data'] ) ) {
				try {
					$args = array_reduce(
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						explode( '&', sanitize_text_field( wp_unslash( $_REQUEST['data'] ) ) ),
						function ( $r, $i ) {
							/** @var array<string,string> $r */
							/** @var string               $i */

							$arg          = explode( '=', $i );
							$r[ $arg[0] ] = urldecode( $arg[1] );
							return $r;
						},
						array()
					);
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}
			foreach ( $args as $name => $value ) {
				if ( 0 !== strpos( $name, 'nab_' ) ) {
					continue;
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ! isset( $_REQUEST[ $name ] ) ) {
					$_POST[ $name ]    = $value;
					$_REQUEST[ $name ] = $value;
				}
			}
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		3
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'fluentform/fluentform.php' ) && ! is_plugin_active( 'fluentform-pro/fluentform-pro.php' ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_fluent_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_fluent_forms', 10, 5 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
