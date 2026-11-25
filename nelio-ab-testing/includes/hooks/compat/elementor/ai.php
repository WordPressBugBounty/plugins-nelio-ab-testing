<?php
/**
 * This file defines AI-related Elementor compatibility hooks.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/elementor
 */

namespace Nelio_AB_Testing\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

use WP_Post;

/**
 * Infers Elementor as the builder used by a post.
 *
 * @param string  $builder Inferred builder identifier.
 * @param WP_Post $post    WordPress post.
 *
 * @return string Builder identifier.
 */
function infer_builder( $builder, $post ) {
	if ( 'unknown' !== $builder || ! did_action( 'elementor/loaded' ) ) {
		return $builder;
	}

	return get_post_meta( $post->ID, '_elementor_edit_mode', true ) ? 'elementor' : $builder;
}
add_filter( 'nab_content_builder', __NAMESPACE__ . '\infer_builder', 10, 2 );
