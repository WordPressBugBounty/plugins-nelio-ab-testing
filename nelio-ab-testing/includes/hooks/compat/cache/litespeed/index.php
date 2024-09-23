<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with LiteSpeed.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\LiteSpeed;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	do_action( 'litespeed_purge_all', 'Nelio A/B Testing' );
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

function maybe_add_nab_alternative_as_dynamic_cookie( $cookies ) {
	return is_cookie_testing_enabled()
		? array_merge( $cookies, array( 'nabAlternative' ) )
		: $cookies;
}//end maybe_add_nab_alternative_as_dynamic_cookie()
add_filter( 'litespeed_vary_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );
add_filter( 'litespeed_vary_curr_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );

function exclude_files( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing';
	$excluded_files[] = 'nabSettings';
	$excluded_files[] = 'nabQuickActionSettings';
	return $excluded_files;
}//end exclude_files()
add_filter( 'litespeed_optimize_js_excludes', __NAMESPACE__ . '\exclude_files' );
add_filter( 'litespeed_optm_js_defer_exc', __NAMESPACE__ . '\exclude_files' );
add_filter( 'litespeed_optm_gm_js_exc', __NAMESPACE__ . '\exclude_files' );

function exclude_overlay( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing-overlay';
	return $excluded_files;
}//end exclude_overlay()
add_filter( 'litespeed_optimize_css_excludes', __NAMESPACE__ . '\exclude_overlay' );

// =======
// HELPERS
// =======

function is_cookie_testing_enabled() {
	$option = get_option( 'nelio-ab-testing_settings' );
	return 'cookie' === nab_array_get( $option, 'alternative_loading.mode' );
}//end is_cookie_testing_enabled()
