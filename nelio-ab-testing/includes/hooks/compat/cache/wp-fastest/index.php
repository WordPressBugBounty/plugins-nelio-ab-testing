<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with WPFastest.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\WPFastest;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	global $wp_fastest_cache;
	if ( ! empty( $wp_fastest_cache ) && method_exists( $wp_fastest_cache, 'deleteCache' ) ) {
		$wp_fastest_cache->deleteCache( true );
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
