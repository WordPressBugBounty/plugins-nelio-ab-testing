<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

use function absint;
use function add_filter;
use function add_meta_box;
use function function_exists;
use function get_edit_post_link;
use function get_post_meta;
use function wp_enqueue_script;
use function wp_register_style;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                          $edit_link   Edit link.
 * @param TPost_Alternative_Attributes|TPost_Control_Attributes $alternative Alternative.
 *
 * @return string|false
 */
function get_edit_link( $edit_link, $alternative ) {
	if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_nab_experiments' ) ) {
		return $edit_link;
	}
	$edit_link = get_edit_post_link( $alternative['postId'], 'unescaped' );
	return ! empty( $edit_link ) ? $edit_link : false;
}
add_filter( 'nab_nab/page_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );
add_filter( 'nab_nab/post_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );
add_filter( 'nab_nab/custom-post-type_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );

/**
 * Callback to register assets.
 *
 * @return void
 */
function register_assets() {

	nab_register_script_with_auto_deps( 'nab-post-experiment-management', 'post-experiment-management', true );

	wp_register_style(
		'nab-post-experiment-management',
		nelioab()->plugin_url . '/assets/dist/css/post-experiment-management.css',
		array( 'wp-admin', 'wp-components', 'nab-components' ),
		nelioab()->plugin_version
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_assets' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\register_assets' );

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
		get_current_screen(),
		'side',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_alternative_edition_meta_boxes' );

/**
 * Callback to enqueue alternative info to be used in metabox or sidebar.
 *
 * @return void
 */
function maybe_enqueue_alternative_info() {
	if ( ! is_an_alternative_being_edited() ) {
		return;
	}

	$settings = array(
		'experimentId'    => get_experiment_id(),
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'postBeingEdited' => absint( $_REQUEST['post'] ?? 0 ),
		'type'            => get_post_type(),
	);

	wp_enqueue_script( 'nab-post-experiment-management' );
	wp_add_inline_script(
		'nab-post-experiment-management',
		sprintf(
			is_gutenberg_page()
				? 'nab.initEditPostAlternativeBlockEditorSidebar( %s )'
				: 'nab.initEditPostAlternativeMetabox( %s )',
			wp_json_encode( $settings )
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_alternative_info' );

/**
 * Filters whether an experiment can be started or not.
 *
 * @param false|string                 $reason     Reason why the experiment can’t be started. Default: `false`.
 * @param \Nelio_AB_Testing_Experiment $experiment The experiment.
 *
 * @return false|string Reason why the experiment can’t be started. Default: `false`.
 *
 * @since 8.5.0
 */
function can_be_started( $reason, $experiment ) {
	$tested_post_id = $experiment->get_tested_post();
	if ( 'publish' !== get_post_status( $tested_post_id ) ) {
		return _x( 'The tested element is not published.', 'text', 'nelio-ab-testing' );
	}

	$alternatives = $experiment->get_alternatives( 'basic' );
	foreach ( $alternatives as $alternative ) {
		$attrs = $alternative['attributes'];
		if ( ! empty( $attrs['isExistingContent'] ) && 'publish' !== get_post_status( absint( $attrs['postId'] ) ) ) {
			return _x( 'One of the alternatives is not published.', 'text', 'nelio-ab-testing' );
		}
	}

	return $reason;
}
add_filter( 'nab_can_nab/page_be_started', __NAMESPACE__ . '\can_be_started', 10, 2 );
add_filter( 'nab_can_nab/post_be_started', __NAMESPACE__ . '\can_be_started', 10, 2 );
add_filter( 'nab_can_nab/custom-post-type_be_started', __NAMESPACE__ . '\can_be_started', 10, 2 );

/**
 * Whether we’re editing an alternative post or not.
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

/**
 * Experiment ID related to the current post or `0` if we’re not editing an alternative.
 *
 * @return int
 */
function get_experiment_id() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_id = absint( $_REQUEST['post'] ?? 0 );
	return absint( get_post_meta( $post_id, '_nab_experiment', true ) );
}

/**
 * Whether we’re using the gutenberg editor or not.
 *
 * @return bool
 */
function is_gutenberg_page() {
	$current_screen = get_current_screen();
	return ! empty( $current_screen ) && $current_screen->is_block_editor();
}
