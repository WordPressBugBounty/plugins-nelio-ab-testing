<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function add_popup_types( $data ) {
	$post_type = get_post_type_object( 'nelio_popup' );
	if ( empty( $post_type ) ) {
		return $data;
	}//end if

	$data['nelio_popup'] = array(
		'name'   => $post_type->name,
		'label'  => $post_type->label,
		'labels' => array(
			'singular_name' => $post_type->labels->singular_name,
		),
		'kind'   => 'entity',
	);
	return $data;
}//end add_popup_types()
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_popup_types' );
