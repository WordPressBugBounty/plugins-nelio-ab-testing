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

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	if ( ! function_exists( 'wp_cache_clean_cache' ) ) {
		return;
	}

	/** @var string */
	global $supercachedir;
	if ( empty( $supercachedir ) && function_exists( 'get_supercache_dir' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$supercachedir = get_supercache_dir();
	}

	/** @var string */
	global $file_prefix;
	wp_cache_clean_cache( $file_prefix );
	wp_cache_clean_cache( $file_prefix, true );
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Ignores nab argument during cookie testing.
 *
 * @return void
 */
function maybe_ignore_nab_arg_during_cookie_testing() {
	/** @var string|null */
	global $wp_cache_plugins_dir;
	if ( empty( $wp_cache_plugins_dir ) ) {
		return;
	}

	$settings = \Nelio_AB_Testing_Settings::instance();
	$value    = $settings->get( 'alternative_loading' );
	$value    = $value['mode'];
	$filename = "{$wp_cache_plugins_dir}/nab-cookie-cache-salting.php";
	if ( 'cookie' === $value ) {
		$src = untrailingslashit( __DIR__ );
		$src = "{$src}/nab-cookie-cache-salting.php";
		copy_cache_file( $src, $filename );
	} else {
		delete_cache_file( $filename );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\maybe_ignore_nab_arg_during_cookie_testing' );

/**
 * Shows notice in Dashboard if config file is missing.
 *
 * @return void
 */
function show_notice_if_config_file_is_missing() {
	/** @var string|null */
	global $wp_cache_plugins_dir;
	if ( empty( $wp_cache_plugins_dir ) ) {
		return;
	}

	$settings = \Nelio_AB_Testing_Settings::instance();
	$value    = $settings->get( 'alternative_loading' );
	$value    = $value['mode'];
	if ( 'cookie' !== $value ) {
		return;
	}

	warn_missing_file(
		'WP Super Cache',
		"{$wp_cache_plugins_dir}/nab-cookie-cache-salting.php",
		untrailingslashit( __DIR__ ) . '/nab-cookie-cache-salting.php'
	);
}
add_action( 'admin_notices', __NAMESPACE__ . '\show_notice_if_config_file_is_missing' );
