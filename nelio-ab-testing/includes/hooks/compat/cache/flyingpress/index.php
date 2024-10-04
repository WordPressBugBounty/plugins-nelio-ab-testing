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

function flush_cache() {
	if ( class_exists( '\FlyingPress\Purge' ) ) {
		\FlyingPress\Purge::purge_everything();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

function exclude_nab_resources( $exclude_keywords ) {
	$exclude_keywords[] = 'nelio-ab-testing';
	return $exclude_keywords;
}//end exclude_nab_resources()
add_action( 'flying_press_exclude_from_minify:css', __NAMESPACE__ . '\exclude_nab_resources' );
add_action( 'flying_press_exclude_from_minify:js', __NAMESPACE__ . '\exclude_nab_resources' );
