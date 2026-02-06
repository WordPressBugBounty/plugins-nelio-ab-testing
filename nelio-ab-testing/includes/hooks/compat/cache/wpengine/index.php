<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with WPEngine’s cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\WPEngine;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {

	if ( ! class_exists( 'WpeCommon' ) ) {
		return;
	}

	// @phpstan-ignore-next-line
	if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
		\WpeCommon::purge_memcached();
	}

	// @phpstan-ignore-next-line
	if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) {
		\WpeCommon::clear_maxcdn_cache();
	}

	// @phpstan-ignore-next-line
	if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
		\WpeCommon::purge_varnish_cache();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
