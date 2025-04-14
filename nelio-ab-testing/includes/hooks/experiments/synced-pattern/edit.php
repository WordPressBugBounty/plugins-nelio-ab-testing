<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

use function absint;
use function add_filter;
use function add_meta_box;
use function function_exists;
use function get_edit_post_link;
use function get_post_meta;

defined( 'ABSPATH' ) || exit;

function get_edit_link( $edit_link, $alternative ) {
	return function_exists( 'current_user_can' ) && current_user_can( 'edit_nab_experiments' )
		? get_edit_post_link( $alternative['patternId'], 'unescaped' )
		: $edit_link;
}//end get_edit_link()
add_filter( 'nab_nab/synced-pattern_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );

function add_alternative_edition_meta_boxes() {

	if ( ! is_an_alternative_being_edited() ) {
		return;
	}//end if

	add_meta_box(
		'nelioab_edit_post_alternative_box', // HTML identifier.
		__( 'Nelio A/B Testing', 'nelio-ab-testing' ), // Box title.
		function () {},
		'wp_block',
		'side',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}//end add_alternative_edition_meta_boxes()
add_action( 'admin_menu', __NAMESPACE__ . '\add_alternative_edition_meta_boxes' );

function is_an_alternative_being_edited() {
	// Check whether we are in the edit page.
	if ( ! isset( $_REQUEST['action'] ) ) { // phpcs:ignore
		return false;
	}//end if
	if ( 'edit' !== $_REQUEST['action'] ) { // phpcs:ignore
		return false;
	}//end if

	// Check whether there is a post_id set. If there is not any,
	// it is a new post, and so we can quit.
	if ( ! isset( $_REQUEST['post'] ) ) { // phpcs:ignore
		return false;
	}//end if
	$post_id = absint( $_REQUEST['post'] ); // phpcs:ignore

	// Determine whether the current post is a nelioab_alternative.
	// If it is not, quit.
	if ( empty( get_post_meta( $post_id, '_nab_experiment', true ) ) ) {
		return false;
	}//end if

	return true;
}//end is_an_alternative_being_edited()
