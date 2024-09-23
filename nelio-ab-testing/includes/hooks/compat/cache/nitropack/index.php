<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Nitropack.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\Nitropack;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	if ( function_exists( 'nitropack_sdk_purge_local' ) ) {
		nitropack_sdk_purge_local();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
