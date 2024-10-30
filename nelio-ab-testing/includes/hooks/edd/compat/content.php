<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Compat;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function remove_edd_types( $data ) {
	unset( $data['download'] );
	return $data;
}//end remove_edd_types()
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\remove_edd_types' );
