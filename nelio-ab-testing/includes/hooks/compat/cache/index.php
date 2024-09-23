<?php
/**
 * This file loads all compatibility hooks for cache plugins and defines a custom action.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.0.7
 */

namespace Nelio_AB_Testing\Compat\Cache;

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/autoptimize/index.php';
require_once dirname( __FILE__ ) . '/breeze/index.php';
require_once dirname( __FILE__ ) . '/cloudflare/index.php';
require_once dirname( __FILE__ ) . '/comet-cache/index.php';
require_once dirname( __FILE__ ) . '/godaddy/index.php';
require_once dirname( __FILE__ ) . '/kinsta/index.php';
require_once dirname( __FILE__ ) . '/litespeed/index.php';
require_once dirname( __FILE__ ) . '/nitropack/index.php';
require_once dirname( __FILE__ ) . '/perfmatters/index.php';
require_once dirname( __FILE__ ) . '/rapidload/index.php';
require_once dirname( __FILE__ ) . '/sg-optimizer/index.php';
require_once dirname( __FILE__ ) . '/w3-total/index.php';
require_once dirname( __FILE__ ) . '/wordpress/index.php';
require_once dirname( __FILE__ ) . '/wpengine/index.php';
require_once dirname( __FILE__ ) . '/wp-fastest/index.php';
require_once dirname( __FILE__ ) . '/wp-optimize/index.php';
require_once dirname( __FILE__ ) . '/wp-rocket/index.php';
require_once dirname( __FILE__ ) . '/wp-super-cache/index.php';

function trigger_flush_all_caches() {
	/**
	 * Triggers a request to flush all compatible caches.
	 *
	 * By default, this action fires when an experiment is started, stopped,
	 * paused, or resumed. Hook into this action to add compatibility with
	 * your own cache plugin.
	 *
	 * @since 5.0.0
	 */
	do_action( 'nab_flush_all_caches' );
}//end trigger_flush_all_caches()
add_action( 'nab_start_experiment', __NAMESPACE__ . '\trigger_flush_all_caches' );
add_action( 'nab_pause_experiment', __NAMESPACE__ . '\trigger_flush_all_caches' );
add_action( 'nab_resume_experiment', __NAMESPACE__ . '\trigger_flush_all_caches' );
add_action( 'nab_stop_experiment', __NAMESPACE__ . '\trigger_flush_all_caches' );

// =======
// HELPERS
// =======

function copy_cache_file( string $src, string $dest ): bool {
	if ( 'direct' !== get_filesystem_method() ) {
		return false;
	}//end if

	$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
	if ( ! WP_Filesystem( $creds ) ) {
		return false;
	}//end if

	global $wp_filesystem;
	if ( ! $wp_filesystem->exists( $src ) ) {
		return false;
	}//end if

	if ( $wp_filesystem->is_dir( $dest ) ) {
		$file = basename( $src );
		$dest = untrailingslashit( $dest ) . $file;
	}//end if

	$dest_folder = untrailingslashit( dirname( $dest ) );
	if ( ! wp_mkdir_p( $dest_folder ) ) {
		return false;
	}//end if

	return $wp_filesystem->copy( $src, $dest );
}//end copy_cache_file()

function delete_cache_file( string $filename ): bool {
	if ( 'direct' !== get_filesystem_method() ) {
		return false;
	}//end if

	$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
	if ( ! WP_Filesystem( $creds ) ) {
		return false;
	}//end if

	global $wp_filesystem;
	return $wp_filesystem->delete( $filename );
}//end delete_cache_file()

function warn_missing_file( string $plugin, string $expected_file, string $source_file ): void {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}//end if

	$screen = get_current_screen();
	if ( false === strpos( $screen->id, 'nelio-ab-testing' ) ) {
		return;
	}//end if

	if ( file_exists( $expected_file ) ) {
		return;
	}//end if

	$prefix = untrailingslashit( dirname( WP_CONTENT_DIR ) );

	$source_file = str_replace( $prefix, '', $source_file );
	$dest_folder = trailingslashit( dirname( $expected_file ) );
	$dest_folder = str_replace( $prefix, '', $dest_folder );

	printf(
		'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
		sprintf(
			/* translators: 1 -> plugin name, 2 -> file name, 3 -> folder name */
			esc_html_x( 'Dynamic caching is enabled in Nelio A/B Testing, but your cache plugin %1$s could not be automatically configured. Please copy file %2$s to %3$s and then purge your cache.', 'user', 'nelio-ab-testing' ),
			sprintf( '<strong>%s</strong>', esc_html( $plugin ) ),
			sprintf( '<code>%s</code>', esc_html( $source_file ) ),
			sprintf( '<code>%s</code>', esc_html( $dest_folder ) )
		)
	);
}//end warn_missing_file()
