<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Callback to add required hooks to load alternative content.
 *
 * @param THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative    Alternative.
 * @param THeadline_Control_Attributes                                  $control        Control.
 * @param int                                                           $experiment_id  Experiment ID.
 * @param string                                                        $alternative_id Alternative ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id, $alternative_id ) {

	if ( is_control( $alternative, $control ) ) {
		return;
	}

	add_filter(
		'the_title',
		function ( $title, $post_id ) use ( $alternative, $control ) {
			/** @var string $title   */
			/** @var int    $post_id */

			if ( $post_id !== $control['postId'] ) {
				return $title;
			}
			if ( empty( $alternative['name'] ) ) {
				return $title;
			}
			return $alternative['name'];
		},
		10,
		2
	);

	add_filter(
		'get_the_excerpt',
		function ( $excerpt, $post ) use ( $alternative, $control ) {
			/** @var string   $excerpt */
			/** @var \WP_Post $post    */

			if ( $post->ID !== $control['postId'] ) {
				return $excerpt;
			}
			if ( empty( $alternative['excerpt'] ) ) {
				return $excerpt;
			}
			return $alternative['excerpt'];
		},
		10,
		2
	);

	add_filter(
		'get_post_metadata',
		function ( $value, $object_id, $meta_key ) use ( $alternative, $control, $alternative_id ) {
			/** @var mixed  $value     */
			/** @var int    $object_id */
			/** @var string $meta_key  */

			if ( '_thumbnail_id' !== $meta_key ) {
				return $value;
			}
			if ( $object_id !== $control['postId'] ) {
				return $value;
			}
			if ( empty( $alternative['imageId'] ) && 'control_backup' !== $alternative_id ) {
				return $value;
			}
			return $alternative['imageId'];
		},
		10,
		3
	);
}
add_action( 'nab_nab/headline_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 4 );

/**
 * Whether the given alternative is the control alternative or not.
 *
 * @param THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative    Alternative.
 * @param THeadline_Control_Attributes                                  $control        Control.
 *
 * @return bool
 *
 * @phpstan-assert-if-true THeadline_Control_Attributes $alternative
 */
function is_control( $alternative, $control ) {
	return ! empty( $alternative['postId'] ) && $alternative['postId'] === $control['postId'];
}
