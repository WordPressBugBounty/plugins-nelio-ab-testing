<?php

namespace Nelio_AB_Testing\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to fix slug in alternative during its creation.
 *
 * @param TPost_Alternative_Attributes $alternative Alternative.
 *
 * @return TPost_Alternative_Attributes
 */
function fix_leadpages_slug_in_alternative_during_its_creation( $alternative ) {

	$post_id = $alternative['postId'];
	$post    = get_post( $post_id );
	if ( empty( $post ) ) {
		return $alternative;
	}

	fix_leadpages_slug_in_alternative( $post_id, $post );
	return $alternative;
}

/**
 * Fixes slug.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Post.
 *
 * @return void
 */
function fix_leadpages_slug_in_alternative( $post_id, $post ) {

	if ( 'leadpages_post' !== $post->post_type ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$experiment = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
	if ( empty( $experiment ) ) {
		return;
	}

	update_post_meta( $post_id, 'leadpages_slug', "nab-alternative-leadpage-{$experiment}-{$post_id}" );
}

/**
 * Callback to prevent alternative front leadpage from overwriting control.
 *
 * @param mixed $value     Value.
 * @param mixed $old_value Old value.
 *
 * @return mixed
 */
function prevent_alternative_front_leadpage_from_overwriting_control( $value, $old_value ) {

	$post_id = absint( $value );
	if ( ! $post_id ) {
		return $value;
	}

	$experiment = get_post_meta( $post_id, '_nab_experiment', true );
	if ( empty( $experiment ) ) {
		return $value;
	}

	return $old_value;
}

add_action(
	'plugins_loaded',
	function () {

		if ( ! class_exists( 'LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType' ) ) {
			return;
		}

		add_filter( 'nab_nab/custom-post-type_create_alternative_content', __NAMESPACE__ . '\fix_leadpages_slug_in_alternative_during_its_creation', 99 );
		add_filter( 'nab_nab/custom-post-type_duplicate_alternative_content', __NAMESPACE__ . '\fix_leadpages_slug_in_alternative_during_its_creation', 99 );
		add_action( 'save_post', __NAMESPACE__ . '\fix_leadpages_slug_in_alternative', 99, 2 );

		add_filter( 'pre_update_option_leadpages_front_page_id', __NAMESPACE__ . '\prevent_alternative_front_leadpage_from_overwriting_control', 99, 2 );
	}
);
