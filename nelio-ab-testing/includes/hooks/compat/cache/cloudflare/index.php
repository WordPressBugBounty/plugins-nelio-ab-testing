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

/**
 * Adds cfasync attribute to script tags.
 *
 * @param array<string,string> $attrs Attributes.
 *
 * @return array<string,string>
 */
function add_data_cfasync_attr( $attrs ) {
	$handle     = 'nelio-ab-testing-main';
	$registered = wp_script_is( $handle, 'registered' );
	$async      = 'async' === wp_scripts()->get_data( $handle, 'strategy' );
	if ( $registered && ! $async ) {
		$attrs['data-cfasync'] = 'false';
	}
	return $attrs;
}
add_filter( 'nab_add_extra_script_attributes', __NAMESPACE__ . '\add_data_cfasync_attr' );
