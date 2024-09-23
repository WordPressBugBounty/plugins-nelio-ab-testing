<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with GoDaddy’s cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\GoDaddy;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	if ( class_exists( '\WPaaS\Cache' ) && function_exists( 'ccfm_godaddy_purge' ) ) {
		ccfm_godaddy_purge();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
