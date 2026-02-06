<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with SG Optimizer.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      5.0.17
 */

namespace Nelio_AB_Testing\Compat\Cache\SG_Optimizer;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Excludes JavaScript files from being optimized.
 *
 * @param list<string> $exclude_list List of exclusions.
 *
 * @return list<string>
 */
function js_exclude( $exclude_list ) {
	$exclude_list[] = 'nelio-ab-testing-main';
	return $exclude_list;
}
add_filter( 'sgo_js_minify_exclude', __NAMESPACE__ . '\js_exclude' );
add_filter( 'sgo_javascript_combine_exclude', __NAMESPACE__ . '\js_exclude' );
add_filter( 'sgo_js_async_exclude', __NAMESPACE__ . '\js_exclude' );

/**
 * Excludes inline scripts from being optimized.
 *
 * @param list<string> $exclude_list List of exclusions.
 *
 * @return list<string>
 */
function js_exclude_inline_script( $exclude_list ) {
	$exclude_list[] = 'nelio-ab-testing-main-before';
	$exclude_list[] = 'nelio-ab-testing-main-after';
	return $exclude_list;
}
add_filter( 'sgo_javascript_combine_excluded_inline_content', __NAMESPACE__ . '\js_exclude_inline_script' );
