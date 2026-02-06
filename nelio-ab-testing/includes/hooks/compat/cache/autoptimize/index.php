<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Autoptimize.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      5.0.6
 */

namespace Nelio_AB_Testing\Compat\Cache\Autoptimize;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	if ( class_exists( 'autoptimizeCache' ) ) {
		\autoptimizeCache::clearall();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Adds Nelio to list of JS exclusions.
 *
 * @param string $exclude Exclusions.
 *
 * @return string
 */
function override_js_exclude( $exclude ) {
	return $exclude . ', nelio-ab-testing';
}
add_filter( 'autoptimize_filter_js_exclude', __NAMESPACE__ . '\override_js_exclude', 10, 1 );
add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false' );
