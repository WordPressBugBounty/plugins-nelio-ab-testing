<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

use function _x;
use function add_action;
use function is_wp_error;
use function register_rest_route;

use function nab_get_experiment;
use function nelioab;

function register_route_for_duplicating_menu() {

	register_rest_route(
		nelioab()->rest_namespace,
		'/menu/duplicate-control',
		array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => __NAMESPACE__ . '\duplicate_menu_callback',
				'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				'args'                => get_args_for_duplicating_menu(),
			),
		)
	);
}//end register_route_for_duplicating_menu()
add_action( 'rest_api_init', __NAMESPACE__ . '\register_route_for_duplicating_menu' );

function duplicate_menu_callback( $request ) {

	$experiment_id  = $request['experiment'];
	$alternative_id = $request['alternative'];

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $experiment;
	}//end if

	if ( 'nab/menu' !== $experiment->get_type() ) {
		return new WP_Error(
			'invalid-experiment-type',
			_x( 'Invalid test type.', 'text', 'nelio-ab-testing' )
		);
	}//end if

	$alternative = $experiment->get_alternative( $alternative_id );
	if ( empty( $alternative ) ) {
		return new WP_Error(
			'alternative-not-found',
			_x( 'Variant not found.', 'text', 'nelio-ab-testing' )
		);
	}//end if

	$control = $experiment->get_alternative( 'control' );
	if ( isset( $control['attributes'] ) && isset( $alternative['attributes'] ) ) {
		duplicate_menu_in_alternative( $control['attributes'], $alternative['attributes'] );
	}//end if
	return new WP_REST_Response( true, 200 );
}//end duplicate_menu_callback()

function get_args_for_duplicating_menu() {
	return array(
		'experiment'  => array(
			'description'       => 'The test in which the duplicated menu should be stored.',
			'type'              => 'integer',
			'sanitize_callback' => '\absint',
		),
		'alternative' => array(
			'description'       => 'The variant in which the duplicated menu should be stored.',
			'type'              => 'string',
			'sanitize_callback' => '\sanitize_text_field',
		),
	);
}//end get_args_for_duplicating_menu()
