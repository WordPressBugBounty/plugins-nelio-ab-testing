<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Compat;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Removes EDD types from the list of testeable post types.
 *
 * @param array<string,TPost_Type> $data Post types.
 *
 * @return array<string,TPost_Type>
 */
function remove_edd_types( $data ) {
	unset( $data['download'] );
	return $data;
}
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\remove_edd_types' );
