<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

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
	$post_type = get_post_type_object( 'nelio_popup' );
	if ( empty( $post_type ) ) {
		return $data;
	}

	$data['nelio_popup'] = array(
		'name'   => $post_type->name,
		'label'  => _x( 'Nelio Popup', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => is_string( $post_type->labels->singular_name ) ? $post_type->labels->singular_name : $post_type->name,
		),
		'kind'   => 'entity',
	);
	return $data;
}
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_popup_types' );
