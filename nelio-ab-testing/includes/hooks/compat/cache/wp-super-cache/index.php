<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with WP_Super_Cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\WP_Super_Cache;

defined( 'ABSPATH' ) || exit;

use function Nelio_AB_Testing\Compat\Cache\copy_cache_file;
use function Nelio_AB_Testing\Compat\Cache\delete_cache_file;
use function Nelio_AB_Testing\Compat\Cache\warn_missing_file;


function flush_cache() {
	if ( ! function_exists( 'wp_cache_clean_cache' ) ) {
		return;
	}//end if
	global $file_prefix, $supercachedir;
	if ( empty( $supercachedir ) && function_exists( 'get_supercache_dir' ) ) {
		$supercachedir = get_supercache_dir();
	}//end if
	wp_cache_clean_cache( $file_prefix );
	wp_cache_clean_cache( $file_prefix, true );
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );


function maybe_ignore_nab_arg_during_cookie_testing() {
	global $wp_cache_plugins_dir;
	if ( empty( $wp_cache_plugins_dir ) ) {
		return;
	}//end if

	$settings = \Nelio_AB_Testing_Settings::instance();
	$value    = $settings->get( 'alternative_loading' );
	$value    = nab_array_get( $value, 'mode' );
	$filename = "{$wp_cache_plugins_dir}/nab-cookie-cache-salting.php";
	if ( 'cookie' === $value ) {
		$src = untrailingslashit( __DIR__ );
		$src = "{$src}/nab-cookie-cache-salting.php";
		copy_cache_file( $src, $filename );
	} else {
		delete_cache_file( $filename );
	}//end if
}//end maybe_ignore_nab_arg_during_cookie_testing()
add_filter( 'admin_init', __NAMESPACE__ . '\maybe_ignore_nab_arg_during_cookie_testing' );


function show_notice_if_config_file_is_missing() {
	global $wp_cache_plugins_dir;
	if ( empty( $wp_cache_plugins_dir ) ) {
		return;
	}//end if

	$settings = \Nelio_AB_Testing_Settings::instance();
	$value    = $settings->get( 'alternative_loading' );
	$value    = nab_array_get( $value, 'mode' );
	if ( 'cookie' !== $value ) {
		return;
	}//end if

	warn_missing_file(
		'WP Super Cache',
		"{$wp_cache_plugins_dir}/nab-cookie-cache-salting.php",
		untrailingslashit( __DIR__ ) . '/nab-cookie-cache-salting.php'
	);
}//end show_notice_if_config_file_is_missing()
add_filter( 'admin_notices', __NAMESPACE__ . '\show_notice_if_config_file_is_missing' );
