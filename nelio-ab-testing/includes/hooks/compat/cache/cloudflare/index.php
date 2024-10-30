<?php
/**
 * This file defines hooks to prevent cloudflare from "optimizing" Nelio’s scripts.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      5.0.22
 */

namespace Nelio_AB_Testing\Compat\Cache\Cloudflare;

defined( 'ABSPATH' ) || exit;

function add_data_cfasync_attr( $attrs ) {
	global $wp_scripts;
	$script = nab_array_get( $wp_scripts->registered, 'nelio-ab-testing-main', false );
	$async  = ! empty( $script ) ? 'async' === nab_array_get( $script->extra, 'strategy', false ) : false;
	if ( ! $async ) {
		$attrs['data-cfasync'] = 'false';
	}//end if
	return $attrs;
}//end add_data_cfasync_attr()
add_filter( 'nab_add_extra_script_attributes', __NAMESPACE__ . '\add_data_cfasync_attr' );
