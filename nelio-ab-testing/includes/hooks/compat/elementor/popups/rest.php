<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function add_popup_types( $data ) {
	$data['nab_elementor_popup'] = array(
		'name'   => 'nab_elementor_popup',
		'label'  => _x( 'Elementor Popup', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'Elementor Popup', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'entity',
	);
	return $data;
}//end add_popup_types()
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_popup_types' );

function get_popup( $result, $post_id, $post_type ) {
	if ( 'nab_elementor_popup' !== $post_type ) {
		return $result;
	}//end if
	return get_post( $post_id );
}//end get_popup()
add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_popup', 10, 3 );

function fix_search_args_for_popups( $args ) {
	if ( 'nab_elementor_popup' !== nab_array_get( $args, 'post_type' ) ) {
		return $args;
	}//end if

	$args['post_type']  = 'elementor_library';
	$args['meta_key']   = '_elementor_template_type'; // phpcs:ignore
	$args['meta_value'] = 'popup'; // phpcs:ignore

	return $args;
}//end fix_search_args_for_popups()
add_filter( 'nab_wp_post_search_args', __NAMESPACE__ . '\fix_search_args_for_popups' );

function fix_popup_type_in_json( $json ) {
	if ( 'elementor_library' !== $json['type'] ) {
		return $json;
	}//end if

	if ( 'popup' !== get_post_meta( $json['id'], '_elementor_template_type', true ) ) {
		return $json;
	}//end if

	$json['type']      = 'nab_elementor_popup';
	$json['typeLabel'] = _x( 'Elementor Popup', 'text', 'nelio-ab-testing' );
	return $json;
}//end fix_popup_type_in_json()
add_filter( 'nab_post_json', __NAMESPACE__ . '\fix_popup_type_in_json' );
