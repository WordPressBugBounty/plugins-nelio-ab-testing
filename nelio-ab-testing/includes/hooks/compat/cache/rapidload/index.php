<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with RapidLoad.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\RapidLoad;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	if ( class_exists( 'RapidLoad_Cache' ) && method_exists( 'RapidLoad_Cache', 'clear_site_cache' ) ) {
		\RapidLoad_Cache::clear_site_cache();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

function exclude_files( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing';
	$excluded_files[] = 'nabSettings';
	$excluded_files[] = 'nabQuickActionSettings';
	return $excluded_files;
}//end exclude_files()
add_filter( 'rapidload/defer/exclusions/js', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rapidload/defer/exclusions/inline_js', __NAMESPACE__ . '\exclude_files', 10, 1 );
