<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

use function add_filter;
use function get_permalink;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get preview link.
 *
 * @param string|false                                          $preview_link   Preview link.
 * @param TPost_Alternative_Attributes|TPost_Control_Attributes $alternative    Alternative.
 * @param TPost_Control_Attributes                              $control        Control.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control ) {
	$link = empty( $control['testAgainstExistingContent'] )
		? get_permalink( $control['postId'] )
		: get_permalink( $alternative['postId'] );
	return ! empty( $link ) ? $link : $preview_link;
}
add_filter( 'nab_nab/page_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );
add_filter( 'nab_nab/post_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );
add_filter( 'nab_nab/custom-post-type_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

add_filter( 'nab_get_nab/page_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
add_filter( 'nab_get_nab/post_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
add_filter( 'nab_get_nab/custom-post-type_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );

/**
 * Callback to overwrite native preview post link.
 *
 * @param string   $link Link.
 * @param \WP_Post $post Link.
 *
 * @return string
 */
function maybe_overwrite_native_preview_post_link( $link, $post ) {
	$post_id = $post->ID;
	$exp_id  = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
	if ( empty( $exp_id ) ) {
		return $link;
	}

	$exp = nab_get_experiment( $exp_id );
	if ( is_wp_error( $exp ) ) {
		return $link;
	}

	$types = array( 'nab/page', 'nab/post', 'nab/custom-post-type' );
	if ( ! in_array( $exp->get_type(), $types, true ) ) {
		return $link;
	}

	$alts = $exp->get_alternatives();
	$alts = array_filter(
		$alts,
		function ( $a ) use ( $post_id ) {
			return (
				isset( $a['attributes']['postId'] ) &&
				absint( $a['attributes']['postId'] ) === $post_id
			);
		}
	);
	$alts = array_values( $alts );

	$alt = empty( $alts ) ? null : $alts[0];
	if ( empty( $alt['links']['preview'] ) ) {
		return $link;
	}

	return $alt['links']['preview'];
}
add_filter( 'preview_post_link', __NAMESPACE__ . '\maybe_overwrite_native_preview_post_link', 10, 2 );
