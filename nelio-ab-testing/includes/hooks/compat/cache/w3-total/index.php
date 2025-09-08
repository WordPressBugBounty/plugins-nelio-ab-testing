<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with W3 Total Cache.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\W3Total;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
