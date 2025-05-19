<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

function get_preview_link( $link, $alternative, $control ) {
	if ( ! is_nelio_popup( $control ) ) {
		return $link;
	}//end if
	return get_preview_post_link( $alternative['postId'] );
}//end get_preview_link()
add_filter( 'nab_nab/popup_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

function disable_anonymous_user_while_previewing_nelio_popup( $anonymous ) {
	return is_previewing_nelio_popup() ? false : $anonymous;
}//end disable_anonymous_user_while_previewing_nelio_popup()
add_filter( 'nab_simulate_anonymous_visitor', __NAMESPACE__ . '\disable_anonymous_user_while_previewing_nelio_popup' );

function hide_admin_bar_while_previewing_nelio_popup( $visible ) {
	return is_previewing_nelio_popup() ? false : $visible;
}//end hide_admin_bar_while_previewing_nelio_popup()
add_filter( 'show_admin_bar', __NAMESPACE__ . '\hide_admin_bar_while_previewing_nelio_popup' ); // phpcs:ignore

function is_previewing_nelio_popup() {
	return isset( $_GET['nelio-popup-preview'] ); // phpcs:ignore
}//end is_previewing_nelio_popup()
