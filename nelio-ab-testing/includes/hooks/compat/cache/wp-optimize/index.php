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

	if ( ! class_exists( 'WP_Optimize' ) || ! defined( 'WPO_PLUGIN_MAIN_PATH' ) ) {
		return;
	}//end if

	if ( ! class_exists( 'WP_Optimize_Cache_Commands' ) ) {
		include_once WPO_PLUGIN_MAIN_PATH . 'cache/class-cache-commands.php';
	}//end if

	if ( class_exists( 'WP_Optimize_Cache_Commands' ) ) {
		$wpoptimize_cache_commands = new \WP_Optimize_Cache_Commands();
		$wpoptimize_cache_commands->purge_page_cache();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );
