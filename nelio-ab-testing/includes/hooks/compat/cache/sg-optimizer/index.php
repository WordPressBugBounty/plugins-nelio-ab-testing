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

function flush_cache() {
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

function js_exclude( $exclude_list ) {
	$exclude_list[] = 'nelio-ab-testing-kickoff';
	$exclude_list[] = 'nelio-ab-testing-alternative-loader';
	$exclude_list[] = 'nelio-ab-testing-main';

	return $exclude_list;
}//end js_exclude()
add_filter( 'sgo_js_minify_exclude', __NAMESPACE__ . '\js_exclude' );
add_filter( 'sgo_javascript_combine_exclude', __NAMESPACE__ . '\js_exclude' );
add_filter( 'sgo_js_async_exclude', __NAMESPACE__ . '\js_exclude' );

function js_exclude_inline_script( $exclude_list ) {
	$exclude_list[] = 'nelio-ab-testing-kickoff-before';
	$exclude_list[] = 'nelio-ab-testing-alternative-loader-before';
	$exclude_list[] = 'nelio-ab-testing-main-before';

	$exclude_list[] = 'nelio-ab-testing-kickoff-after';
	$exclude_list[] = 'nelio-ab-testing-alternative-loader-after';
	$exclude_list[] = 'nelio-ab-testing-main-after';

	return $exclude_list;
}//end js_exclude_inline_script()
add_filter( 'sgo_javascript_combine_excluded_inline_content', __NAMESPACE__ . '\js_exclude_inline_script' );
