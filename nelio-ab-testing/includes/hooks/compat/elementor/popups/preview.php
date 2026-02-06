<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to skip preview args.
 *
 * @param bool                      $skip        Whether query args should be skip or not. Default: `false`.
 * @param TIgnore                   $alternative Current alternative.
 * @param TPopup_Control_Attributes $control     Original version.
 *
 * @return bool
 */
function skip_preview_args( $skip, $alternative, $control ) {
	return is_elementor( $control ) ? true : $skip;
}
add_filter( 'nab_nab/popup_skip_preview_args_alternative', __NAMESPACE__ . '\skip_preview_args', 10, 3 );

/**
 * Callback to return the preview link.
 *
 * @param string|false                                            $link        Link.
 * @param TPopup_Control_Attributes|TPopup_Alternative_Attributes $alternative Alternative.
 * @param TPopup_Control_Attributes                               $control     Control.
 *
 * @return string|false
 */
function get_preview_link( $link, $alternative, $control ) {
	if ( ! is_elementor( $control ) ) {
		return $link;
	}
	return add_query_arg(
		array(
			'nab-elementor-preview' => 1,
			'nab-elementor-reload'  => 1,
		),
		get_preview_post_link( $alternative['postId'] )
	);
}
add_filter( 'nab_nab/popup_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

/**
 * Callback to hide admin bar in preview.
 *
 * @param bool $visible Visible.
 *
 * @return bool
 */
function show_admin_bar_in_preview( $visible ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['nab-elementor-preview'] ) ? false : $visible;
}
// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
add_filter( 'show_admin_bar', __NAMESPACE__ . '\show_admin_bar_in_preview' );

/**
 * Callback to add a reload script if needed.
 *
 * @return void
 */
function maybe_add_reload_script() {
	// This function is a workaround because, for some reason, the popup
	// doesn’t show up in preview iframe.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-elementor-reload'] ) ) {
		return;
	}
	$script = '
		setTimeout( () => {
			window.location.href = window.location.href
				.replace( /&nab-elementor-reload=1/, "" )
				.replace( /\?nab-elementor-reload=1&/, "?" )
				.replace( /\?nab-elementor-reload=1#/, "#" )
				.replace( /\?nab-elementor-reload=1/, "" );
		},
		1000
	);
	';
	wp_print_inline_script_tag( $script );
}
add_action( 'wp_footer', __NAMESPACE__ . '\maybe_add_reload_script', 999 );
