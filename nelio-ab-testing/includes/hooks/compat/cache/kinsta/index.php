<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Kinsta’s cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\Kinsta;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	/** @var \Kinsta\Cache|null */
	global $kinsta_cache;
	if ( class_exists( '\Kinsta\Cache' ) && ! empty( $kinsta_cache ) ) {
		$kinsta_cache->kinsta_cache_purge->purge_complete_caches();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
