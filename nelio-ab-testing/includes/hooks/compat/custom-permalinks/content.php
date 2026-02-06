<?php

namespace Nelio_AB_Testing\Compat\Custom_Permalinks;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function get_post_meta;
use function get_the_ID;

/**
 * Callback to remove meta box in alternative.
 *
 * @return void
 */
function remove_meta_box_in_alternative() {
	if ( ! is_alternative( absint( get_the_ID() ) ) ) {
		return;
	}
	remove_meta_box( 'custom-permalinks-edit-box', array(), 'normal' );
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\remove_meta_box_in_alternative', 99 );

/**
 * Callback to remove custom permalink in alternative.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function remove_custom_permalink_in_alternative( $post_id ) {
	if ( ! is_alternative( $post_id ) ) {
		return;
	}
	delete_post_meta( $post_id, 'custom_permalink' );
}
add_action( 'nab_overwrite_post', __NAMESPACE__ . '\remove_custom_permalink_in_alternative' );

/**
 * Callback to save permalink before applying an alternative.
 *
 * @param bool                     $applied Applied.
 * @param TIgnore                  $alternative Alternative.
 * @param TPost_Control_Attributes $control Control.
 *
 * @return bool
 */
function save_permalink_before_apply( $applied, $alternative, $control ) {
	$permalink = get_post_meta( $control['postId'], 'custom_permalink', true );
	if ( ! empty( $permalink ) ) {
		update_post_meta( $control['postId'], '_nab_custom_permalink_backup', $permalink );
	}
	return $applied;
}
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\save_permalink_before_apply', 9, 3 );

/**
 * Callback to restore proper permalink after applying an alternative.
 *
 * @param bool                     $applied Applied.
 * @param TIgnore                  $alternative Alternative.
 * @param TPost_Control_Attributes $control Control.
 *
 * @return bool
 */
function restore_permalink_after_apply( $applied, $alternative, $control ) {
	$permalink = get_post_meta( $control['postId'], '_nab_custom_permalink_backup', true );
	if ( ! empty( $permalink ) ) {
		delete_post_meta( $control['postId'], 'custom_permalink' );
		add_post_meta( $control['postId'], 'custom_permalink', $permalink );
	}
	delete_post_meta( $control['postId'], '_nab_custom_permalink_backup' );
	return $applied;
}
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\restore_permalink_after_apply', 11, 3 );

/**
 * Whether the given post is alternative content or not.
 *
 * @param int $post_id Post ID.
 *
 * @return bool
 */
function is_alternative( $post_id ) {
	return 'nab_hidden' === get_post_status( $post_id );
}
