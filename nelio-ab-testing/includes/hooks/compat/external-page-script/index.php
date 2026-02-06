<?php
/**
 * This compat file prints sends JSON data with the info needed to run a test on the current page.
 */
namespace Nelio_AB_Testing\Compat\External_Page_Script;

defined( 'ABSPATH' ) || exit;

/**
 * Sends JSON data with info needed to run external script if request has the `nab-external-page-script` query arg in it.
 *
 * @return void
 */
function print_json_for_external_page_script() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-external-page-script'] ) ) {
		return;
	}

	/**
	 * Runs before wp_enqueue_scripts action during JSON generation for external page scripts.
	 *
	 * @since 8.2.0
	 */
	do_action( 'nab_external_page_script_before_enqueue_scripts' );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	do_action( 'wp_enqueue_scripts' );

	/**
	 * Runs after wp_enqueue_scripts action during JSON generation for external page scripts.
	 *
	 * @since 8.2.0
	 */
	do_action( 'nab_external_page_script_after_enqueue_scripts' );

	ob_start();
	wp_print_styles();
	wp_print_scripts();
	/**
	 * Prints additional stuff on the external page’s head.
	 *
	 * @since 8.2.0
	 */
	do_action( 'nab_external_page_script_print_assets' );
	$result = ob_get_clean();

	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( $result );
	die();
}
add_action( 'wp', __NAMESPACE__ . '\print_json_for_external_page_script', 9999 );

/**
 * Removes unnecessary assets.
 *
 * @return void
 */
function remove_unnecessary_assets() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-external-page-script'] ) ) {
		return;
	}

	/** @var \WP_Scripts */
	global $wp_scripts;

	wp_add_inline_script( 'nelio-ab-testing-main', 'const nabKeepOverlayInExternalPageScript = true;', 'before' );

	/**
	 * Filters the scripts that can be added to external pages via Nelio’s external page script.
	 *
	 * @param list<string> $scripts Script handles. Default: `array()`.
	 */
	$allowed_scripts = apply_filters( 'nab_scripts_to_keep_in_external_pages', array() );
	foreach ( $wp_scripts->queue as $handle ) {
		if ( ! is_nab_handle( $handle ) && ! in_array( $handle, $allowed_scripts, true ) ) {
			wp_dequeue_script( $handle );
		}
	}

	/** @var \WP_Styles */
	global $wp_styles;

	/**
	 * Filters the styles that can be added to external pages via Nelio’s external page script.
	 *
	 * @param list<string> $scripts Style handles. Default: `array()`.
	 */
	$allowed_styles = apply_filters( 'nab_styles_to_keep_in_external_pages', array() );
	foreach ( $wp_styles->queue as $handle ) {
		if ( ! is_nab_handle( $handle ) && ! in_array( $handle, $allowed_styles, true ) ) {
			wp_dequeue_style( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\remove_unnecessary_assets', 9999 );

/**
 * Whether the given handle is a Nelio A/B Testing handle or not.
 *
 * @param string $handle Handle.
 *
 * @return bool
 */
function is_nab_handle( $handle ) {
	return 0 === strpos( $handle, 'nab-' ) || 0 === strpos( $handle, 'nelio-ab-testing-' ) || strpos( $handle, 'nelioab-' );
}
