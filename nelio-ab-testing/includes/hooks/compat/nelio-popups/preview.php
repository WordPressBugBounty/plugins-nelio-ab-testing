<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

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
	if ( ! is_nelio_popup( $control ) ) {
		return $link;
	}
	$link = get_preview_post_link( $alternative['postId'] );
	$link = ! empty( $link ) ? $link : false;
	return $link;
}
add_filter( 'nab_nab/popup_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

/**
 * Callback to disable anonymous user while previewing popup.
 *
 * @param bool $anonymous Anonymous user.
 *
 * @return bool
 */
function disable_anonymous_user_while_previewing_nelio_popup( $anonymous ) {
	return is_previewing_nelio_popup() ? false : $anonymous;
}
add_filter( 'nab_simulate_anonymous_visitor', __NAMESPACE__ . '\disable_anonymous_user_while_previewing_nelio_popup' );

/**
 * Callback to hide admin bar while previewing popup.
 *
 * @param bool $visible Visible.
 *
 * @return bool
 */
function hide_admin_bar_while_previewing_nelio_popup( $visible ) {
	return is_previewing_nelio_popup() ? false : $visible;
}
// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
add_filter( 'show_admin_bar', __NAMESPACE__ . '\hide_admin_bar_while_previewing_nelio_popup' );

/**
 * Whether a popup is being previewed.
 *
 * @return bool
 */
function is_previewing_nelio_popup() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['nelio-popup-preview'] );
}
