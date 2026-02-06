<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Comet Cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\Comet_Cache;

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
	if ( class_exists( '\WebSharks\CometCache\Classes\ApiBase' ) ) {
		\WebSharks\CometCache\Classes\ApiBase::clear();
	} elseif ( class_exists( '\WebSharks\CometCache\Pro\Classes\ApiBase' ) ) {
		\WebSharks\CometCache\Pro\Classes\ApiBase::clear();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Ignores nab arg during cookie testing.
 *
 * @return void
 */
function maybe_ignore_nab_arg_during_cookie_testing() {
	if ( ! is_comet_cache_active() ) {
		return;
	}

	$settings = \Nelio_AB_Testing_Settings::instance();
	$value    = $settings->get( 'alternative_loading' );
	$value    = $value['mode'];
	$filename = untrailingslashit( WP_CONTENT_DIR ) . '/ac-plugins/nab-cookie-cache-salting.php';
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
	if ( ! is_comet_cache_active() ) {
		return;
	}

	$settings = \Nelio_AB_Testing_Settings::instance();
	$value    = $settings->get( 'alternative_loading' );
	$value    = $value['mode'];
	if ( 'cookie' !== $value ) {
		return;
	}

	warn_missing_file(
		'Comet Cache',
		untrailingslashit( WP_CONTENT_DIR ) . '/ac-plugins/nab-cookie-cache-salting.php',
		untrailingslashit( __DIR__ ) . '/nab-cookie-cache-salting.php'
	);
}
add_action( 'admin_notices', __NAMESPACE__ . '\show_notice_if_config_file_is_missing' );

/**
 * Whether comet cache is active or not.
 *
 * @return bool
 */
function is_comet_cache_active() {
	return (
		class_exists( '\WebSharks\CometCache\Classes\ApiBase' ) ||
		class_exists( '\WebSharks\CometCache\Pro\Classes\ApiBase' )
	);
}
