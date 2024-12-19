<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

use function absint;
use function add_filter;
use function add_meta_box;
use function array_push;
use function function_exists;
use function get_edit_post_link;
use function get_post_meta;
use function get_post_types;
use function method_exists;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_style;

defined( 'ABSPATH' ) || exit;

function get_edit_link( $edit_link, $alternative ) {
	return function_exists( 'current_user_can' ) && current_user_can( 'edit_nab_experiments' )
		? get_edit_post_link( $alternative['postId'], 'unescaped' )
		: $edit_link;
}//end get_edit_link()
add_filter( 'nab_nab/page_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );
add_filter( 'nab_nab/post_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );
add_filter( 'nab_nab/custom-post-type_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );

function register_assets() {

	nab_register_script_with_auto_deps( 'nab-post-experiment-management', 'post-experiment-management', true );

	wp_register_style(
		'nab-post-experiment-management',
		nelioab()->plugin_url . '/assets/dist/css/post-experiment-management.css',
		array( 'wp-admin', 'wp-components', 'nab-components' ),
		nelioab()->plugin_version
	);
}//end register_assets()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_assets' );
add_filter( 'enqueue_block_editor_assets', __NAMESPACE__ . '\register_assets' );

function add_alternative_edition_meta_boxes() {

	if ( ! is_an_alternative_being_edited() ) {
		return;
	}//end if

	// Recover post type names.
	$args = array(
		'public'   => true,
		'_builtin' => false,
	);

	$post_types = get_post_types( $args, 'names', 'and' );
	array_push( $post_types, 'post', 'page' );
	foreach ( $post_types as $post_type ) {

		add_meta_box(
			'nelioab_edit_post_alternative_box', // HTML identifier.
			__( 'Nelio A/B Testing', 'nelio-ab-testing' ), // Box title.
			function () {},
			$post_type,
			'side',
			'high',
			array(
				'__back_compat_meta_box' => true,
			)
		);

	}//end foreach
}//end add_alternative_edition_meta_boxes()
add_action( 'admin_menu', __NAMESPACE__ . '\add_alternative_edition_meta_boxes' );

function maybe_load_alternative_edition_metabox_content() {
	if ( ! is_an_alternative_being_edited() || is_gutenberg_page() ) {
		return;
	}//end if

	$settings = array(
		'experimentId'    => get_experiment_id(),
		'postBeingEdited' => absint( nab_array_get( $_REQUEST, 'post', 0 ) ), // phpcs:ignore
		'type'            => get_post_type(),
	);

	wp_enqueue_style( 'nab-post-experiment-management' );
	wp_enqueue_script( 'nab-post-experiment-management' );
	wp_add_inline_script(
		'nab-post-experiment-management',
		sprintf(
			'nab.initEditPostAlternativeMetabox( %s )',
			wp_json_encode( $settings )
		)
	);
}//end maybe_load_alternative_edition_metabox_content()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_load_alternative_edition_metabox_content' );

function maybe_load_block_editor_alternative_sidebar_content() {
	if ( ! is_an_alternative_being_edited() || ! is_gutenberg_page() ) {
		return;
	}//end if

	$settings = array(
		'experimentId'    => get_experiment_id(),
		'postBeingEdited' => absint( nab_array_get( $_REQUEST, 'post', 0 ) ), // phpcs:ignore
		'type'            => get_post_type(),
	);

	wp_enqueue_style( 'nab-post-experiment-management' );
	wp_enqueue_script( 'nab-post-experiment-management' );
	wp_add_inline_script(
		'nab-post-experiment-management',
		sprintf(
			'nab.initEditPostAlternativeBlockEditorSidebar( %s )',
			wp_json_encode( $settings )
		)
	);
}//end maybe_load_block_editor_alternative_sidebar_content()
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_load_block_editor_alternative_sidebar_content' );

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

function get_experiment_id() {
	$post_id = absint( nab_array_get( $_REQUEST, 'post' ) ); // phpcs:ignore
	return get_post_meta( $post_id, '_nab_experiment', true );
}//end get_experiment_id()

function is_gutenberg_page() {
	if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
		// The Gutenberg plugin is on.
		return true;
	}//end if

	$current_screen = get_current_screen();
	if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
		// Gutenberg page on 5+.
		return true;
	}//end if
	return false;
}//end is_gutenberg_page()
