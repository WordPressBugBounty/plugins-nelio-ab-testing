<?php

namespace Nelio_AB_Testing\Compat\FluentForms;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

use function Nelio_AB_Testing\Conversion_Action_Library\Form_Submission\maybe_sync_event_submission;

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
}//end add_form_types()

function get_fluent_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}//end if

	if ( 'nab_fluent_form' !== $post_type ) {
		return $post;
	}//end if

	$form = \FluentForm\App\Models\Form::where( 'id', $post_id )->first();

	if ( empty( $form ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: Form ID */
				_x( 'Fluent form with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}//end if

	return array(
		'id'          => $form['id'],
		'title'       => $form['title'],
		'excerpt'     => '',
		'imageId'     => 0,
		'imageSrc'    => '',
		'type'        => 'nab_fluent_form',
		'typeLabel'   => _x( 'Forminator Form', 'text', 'nelio-ab-testing' ),
		'status'      => '',
		'statusLabel' => '',
		'link'        => '',
	);
}//end get_fluent_form()

function search_fluent_forms( $result, $post_type, $term, $per_page, $page ) {
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( 'nab_fluent_form' !== $post_type ) {
		return $result;
	}//end if

	$forms = \FluentForm\App\Models\Form::select( array( 'id', 'title', 'status' ) )
		->where( 'status', 'published' )
		->orderBy( 'title', 'DESC' )
		->get();
	$forms = $forms->getDictionary();
	$forms = array_map( fn( $f ) => $f->getAttributes(), $forms );
	$forms = array_values( $forms );

	$term = strtolower( trim( $term ) );
	if ( ! empty( $term ) ) {
		$form_id = absint( $term );
		$forms   = array_values(
			array_filter(
				$forms,
				fn( $form ) => (
					absint( $form['id'] ) === $form_id ||
					false !== strpos( strtolower( $form['title'] ), $term )
				)
			)
		);
	}//end if

	$forms = array_map(
		function ( $form ) {
			return array(
				'id'          => absint( $form['id'] ),
				'title'       => $form['title'],
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
			'more'  => count( $forms ) === $per_page,
			'pages' => empty( $page ) ? 1 : $page,
		),
	);
}//end search_fluent_forms()

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {
	if ( 'nab_fluent_form' !== $action['formType'] ) {
		return;
	}//end if
	add_action(
		'fluentform/notify_on_form_submit',
		function ( $entry_id, $form_data, $form ) use ( $action, $experiment_id, $goal_index ) {
			if ( absint( $form->getAttributes()['id'] ) !== $action['formId'] ) {
				return;
			}//end if
			$args = array();
			if ( isset( $_REQUEST['data'] ) ) { // phpcs:ignore
				try {
					$args = array_reduce(
						explode( '&', $_REQUEST['data'] ), // phpcs:ignore
						function ( $r, $i ) {
							$arg          = explode( '=', $i );
							$r[ $arg[0] ] = urldecode( $arg[1] );
							return $r;
						},
						array()
					);
				} catch ( \Exception $e ) { // phpcs:ignore
				}//end try
			}//end if
			foreach ( $args as $name => $value ) {
				if ( 0 !== strpos( $name, 'nab_' ) ) {
					continue;
				}//end if
				if ( isset( $args[ $name ] ) && ! isset( $_REQUEST[ $name ] ) ) { // phpcs:ignore
					$_POST[ $name ]    = $value;
					$_REQUEST[ $name ] = $value;
				}//end if
			}//end foreach
			maybe_sync_event_submission( $experiment_id, $goal_index );
		},
		10,
		3
	);
}//end add_hooks_for_tracking()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'fluentform/fluentform.php' ) && ! is_plugin_active( 'fluentform-pro/fluentform-pro.php' ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_fluent_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_fluent_forms', 10, 5 );
		add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );
	}
);
