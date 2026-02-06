<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Adds a new post type.
 *
 * @param array<string,TPost_Type> $data Post types.
 *
 * @return array<string,TPost_Type>
 */
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
}
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_popup_types' );

/**
 * Callback to return the appropriate popup.
 *
 * @param null|TPost|\WP_Post|\WP_Error $result    The post to filter.
 * @param int|string                    $post_id   The id of the post.
 * @param string                        $post_type The post type.
 *
 * @return null|TPost|\WP_Post|\WP_Error
 */
function get_popup( $result, $post_id, $post_type ) {
	if ( 'nab_elementor_popup' !== $post_type ) {
		return $result;
	}
	return get_post( absint( $post_id ) );
}
add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_popup', 10, 3 );

/**
 * Callback to fix search arguments for popups.
 *
 * @param array<string,mixed> $args Args.
 *
 * @return array<string,mixed>
 */
function fix_search_args_for_popups( $args ) {
	$post_type = $args['post_type'] ?? '';
	if ( 'nab_elementor_popup' !== $post_type ) {
		return $args;
	}

	$args['post_type'] = 'elementor_library';
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	$args['meta_key'] = '_elementor_template_type';
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	$args['meta_value'] = 'popup';

	return $args;
}
add_filter( 'nab_wp_post_search_args', __NAMESPACE__ . '\fix_search_args_for_popups' );

/**
 * Callback to fix popup type in JSON.
 *
 * @param TPost $json Args.
 *
 * @return TPost
 */
function fix_popup_type_in_json( $json ) {
	if ( 'elementor_library' !== $json['type'] ) {
		return $json;
	}

	if ( 'popup' !== get_post_meta( absint( $json['id'] ), '_elementor_template_type', true ) ) {
		return $json;
	}

	$json['type']      = 'nab_elementor_popup';
	$json['typeLabel'] = _x( 'Elementor Popup', 'text', 'nelio-ab-testing' );
	return $json;
}
add_filter( 'nab_post_json', __NAMESPACE__ . '\fix_popup_type_in_json' );
