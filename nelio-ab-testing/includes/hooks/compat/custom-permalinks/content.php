<?php

namespace Nelio_AB_Testing\Compat\Custom_Permalinks;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function get_post_meta;
use function get_the_ID;

function remove_meta_box_in_alternative() {
	if ( ! is_alternative( get_the_ID() ) ) {
		return;
	}//end if
	remove_meta_box( 'custom-permalinks-edit-box', null, 'normal' );
}//end remove_meta_box_in_alternative()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\remove_meta_box_in_alternative', 99 );

function remove_custom_permalink_in_alternative( $post_id ) {
	if ( ! is_alternative( $post_id ) ) {
		return;
	}//end if
	delete_post_meta( $post_id, 'custom_permalink' );
}//end remove_custom_permalink_in_alternative()
add_action( 'nab_overwrite_post', __NAMESPACE__ . '\remove_custom_permalink_in_alternative' );

function save_permalink_before_apply( $applied, $_, $control ) {
	$permalink = get_post_meta( $control['postId'], 'custom_permalink', true );
	if ( ! empty( $permalink ) ) {
		update_post_meta( $control['postId'], '_nab_custom_permalink_backup', $permalink );
	}//end if
	return $applied;
}//end save_permalink_before_apply()
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\save_permalink_before_apply', 9, 3 );

function restore_permalink_after_apply( $applied, $_, $control ) {
	$permalink = get_post_meta( $control['postId'], '_nab_custom_permalink_backup', true );
	if ( ! empty( $permalink ) ) {
		delete_post_meta( $control['postId'], 'custom_permalink' );
		add_post_meta( $control['postId'], 'custom_permalink', $permalink );
	}//end if
	delete_post_meta( $control['postId'], '_nab_custom_permalink_backup' );
	return $applied;
}//end restore_permalink_after_apply()
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\restore_permalink_after_apply', 11, 3 );

function is_alternative( $post_id ) {
	return 'nab_hidden' === get_post_status( $post_id );
}//end is_alternative()
