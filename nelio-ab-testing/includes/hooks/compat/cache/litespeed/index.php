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

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	do_action( 'litespeed_purge_all', 'Nelio A/B Testing' );
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Callback to add nabAlternative as a dynamic cookie, if cookie testing is enabled.
 *
 * @param list<string> $cookies Cookies.
 *
 * @return list<string>
 */
function maybe_add_nab_alternative_as_dynamic_cookie( $cookies ) {
	return is_cookie_testing_enabled()
		? array_merge( $cookies, array( 'nabAlternative' ) )
		: $cookies;
}
add_filter( 'litespeed_vary_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );
add_filter( 'litespeed_vary_curr_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );

/**
 * Excludes JavaScript files from being optimized.
 *
 * @param list<string> $excluded_files List of exclusions.
 *
 * @return list<string>
 */
function exclude_files( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing';
	$excluded_files[] = 'nabSettings';
	$excluded_files[] = 'nabQuickActionSettings';
	return $excluded_files;
}
add_filter( 'litespeed_optimize_js_excludes', __NAMESPACE__ . '\exclude_files' );
add_filter( 'litespeed_optm_js_defer_exc', __NAMESPACE__ . '\exclude_files' );
add_filter( 'litespeed_optm_gm_js_exc', __NAMESPACE__ . '\exclude_files' );

/**
 * Excludes CSS files from being optimized.
 *
 * @param list<string> $excluded_files List of exclusions.
 *
 * @return list<string>
 */
function exclude_overlay( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing-overlay';
	return $excluded_files;
}
add_filter( 'litespeed_optimize_css_excludes', __NAMESPACE__ . '\exclude_overlay' );

// =======
// HELPERS
// =======

/**
 * Whether cookie testing is enabled or not.
 *
 * @return bool
 */
function is_cookie_testing_enabled() {
	/** @var array{alternative_loading?:array{mode?:string}} */
	$option = get_option( 'nelio-ab-testing_settings' );
	$mode   = $option['alternative_loading']['mode'] ?? 'redirection';
	return 'cookie' === $mode;
}
