<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Settings;

/**
 * Whether we should use the control ID in alternative content or not.
 *
 * The value comes from a setting, but it’s also filtered.
 *
 * @return bool
 */
function use_control_id_in_alternative() {
	$settings       = Nelio_AB_Testing_Settings::instance();
	$use_control_id = ! empty( $settings->get( 'use_control_id_in_alternative' ) );

	/**
	 * Whether we should use the original post ID when loading an alternative post or not.
	 *
	 * @param bool $use_control_id whether we should use the original post ID or not.
	 *
	 * @since 5.0.4
	 */
	return apply_filters( 'nab_use_control_id_in_alternative', $use_control_id );
}

/**
 * Gets testable custom post types.
 *
 * @return list<string>
 */
function get_testable_custom_post_types() {
	$exclusions = array( 'page', 'post', 'attachment', 'wp_block' );
	$post_types = get_post_types( array( 'public' => true ), 'names' );
	$post_types = array_filter(
		$post_types,
		fn( $pt ) => ! in_array( $pt, $exclusions, true )
	);

	$partial_exclusions = array( 'popup', 'form', 'block' );
	$post_types         = array_filter(
		$post_types,
		fn( $pt ) => array_reduce(
			$partial_exclusions,
			fn( $keep_pt, $exclusion ) => $keep_pt ? strpos( $pt, $exclusion ) === false : false,
			true
		)
	);

	$post_types = array_values( $post_types );

	/**
	 * Filters the list of testable custom post types, available in Custom Post Type tests.
	 *
	 * @param list<string> $post_types List of post types.
	 *
	 * @since 8.5.0
	 */
	return apply_filters( 'nab_get_testable_custom_post_types', $post_types );
}
