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

function flush_cache() {

	if ( ! class_exists( 'WpeCommon' ) ) {
		return;
	}//end if

	if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
		\WpeCommon::purge_memcached();
	}//end if

	if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) {
		\WpeCommon::clear_maxcdn_cache();
	}//end if

	if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
		\WpeCommon::purge_varnish_cache();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
