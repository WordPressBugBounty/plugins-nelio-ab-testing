<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with WPOptimize.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache\WPOptimize;

defined( 'ABSPATH' ) || exit;

function flush_cache() {
	if ( ! class_exists( 'WP_Optimize' ) ) {
		return;
	}//end if

	if ( ! is_callable( array( 'WP_Optimize', 'get_page_cache' ), 'purge' ) ) {
		return;
	}//end if

	\WP_Optimize()->get_page_cache()->purge();
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
