<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Breeze.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\Breeze;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	do_action( 'breeze_clear_all_cache' );
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
