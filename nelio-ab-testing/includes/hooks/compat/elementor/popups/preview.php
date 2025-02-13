<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

function skip_preview_args( $skip, $_, $control ) {
	return is_elementor( $control ) ? true : $skip;
}//end skip_preview_args()
add_filter( 'nab_nab/popup_skip_preview_args_alternative', __NAMESPACE__ . '\skip_preview_args', 10, 3 );

function get_preview_link( $link, $alternative, $control ) {
	if ( ! is_elementor( $control ) ) {
		return $link;
	}//end if
	return add_query_arg(
		array(
			'nab-elementor-preview' => 1,
			'nab-elementor-reload'  => 1,
		),
		get_preview_post_link( $alternative['postId'] )
	);
}//end get_preview_link()
add_filter( 'nab_nab/popup_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

function show_admin_bar_in_preview( $visible ) {
	return isset( $_GET['nab-elementor-preview'] ) ? false : $visible; // phpcs:ignore
}//end show_admin_bar_in_preview()
add_filter( 'show_admin_bar', __NAMESPACE__ . '\show_admin_bar_in_preview', 10, 3 ); // phpcs:ignore

function maybe_add_reload_script() {
	// This function is a workaround because, for some reason, the popup
	// doesn’t show up in preview iframe.
	if ( ! isset( $_GET['nab-elementor-reload'] ) ) { // phpcs:ignore
		return;
	}//end if
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
}//end maybe_add_reload_script()
add_action( 'wp_footer', __NAMESPACE__ . '\maybe_add_reload_script', 999 );
