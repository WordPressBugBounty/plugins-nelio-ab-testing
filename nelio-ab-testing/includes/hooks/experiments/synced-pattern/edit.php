<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

use function absint;
use function add_filter;
use function add_meta_box;
use function function_exists;
use function get_edit_post_link;
use function get_post_meta;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                                              $edit_link   Edit link.
 * @param TSynced_Pattern_Alternative_Attributes|TSynced_Pattern_Control_Attributes $alternative Alternative.
 *
 * @return string|false
 */
function get_edit_link( $edit_link, $alternative ) {
	if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_nab_experiments' ) ) {
		return $edit_link;
	}
	$edit_link = get_edit_post_link( $alternative['patternId'], 'unescaped' );
	return ! empty( $edit_link ) ? $edit_link : false;
}
add_filter( 'nab_nab/synced-pattern_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );

/**
 * Callback to add alternative edition meta boxes.
 *
 * @return void
 */
function add_alternative_edition_meta_boxes() {

	if ( ! is_an_alternative_being_edited() ) {
		return;
	}

	add_meta_box(
		'nelioab_edit_post_alternative_box', // HTML identifier.
		'Nelio A/B Testing', // Box title.
		function () {},
		'wp_block',
		'side',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\add_alternative_edition_meta_boxes' );

/**
 * Whether we’re editing an alternative synced pattern or not.
 *
 * @return bool
 */
function is_an_alternative_being_edited() {
	// Check whether we are in the edit page.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_REQUEST['action'] ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'edit' !== $_REQUEST['action'] ) {
		return false;
	}

	// Check whether there is a post_id set. If there is not any,
	// it is a new post, and so we can quit.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_REQUEST['post'] ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_id = absint( $_REQUEST['post'] );

	// Determine whether the current post is a nelioab_alternative.
	// If it is not, quit.
	if ( empty( get_post_meta( $post_id, '_nab_experiment', true ) ) ) {
		return false;
	}

	return true;
}
