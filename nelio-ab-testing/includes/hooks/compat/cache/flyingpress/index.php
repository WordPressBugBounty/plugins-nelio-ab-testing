<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with FlyingPress’ cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      7.1.2
 */

namespace Nelio_AB_Testing\Compat\Cache\FlyingPress;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	if ( class_exists( '\FlyingPress\Purge' ) ) {
		\FlyingPress\Purge::purge_everything();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Excludes JavaScript and CSS files from being optimized.
 *
 * @param list<string> $exclude_keywords List of exclusions.
 *
 * @return list<string>
 */
function exclude_nab_resources( $exclude_keywords ) {
	$exclude_keywords[] = 'nelio-ab-testing';
	return $exclude_keywords;
}
add_filter( 'flying_press_exclude_from_minify:css', __NAMESPACE__ . '\exclude_nab_resources' );
add_filter( 'flying_press_exclude_from_minify:js', __NAMESPACE__ . '\exclude_nab_resources' );
