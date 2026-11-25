<?php
/**
 * This file defines AI-related Divi compatibility hooks.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/divi
 */

namespace Nelio_AB_Testing\Compat\Divi;

defined( 'ABSPATH' ) || exit;

use WP_Post;

/**
 * Infers Divi as the builder used by a post.
 *
 * @param string  $builder Inferred builder identifier.
 * @param WP_Post $post    WordPress post.
 *
 * @return string Builder identifier.
 */
function infer_builder( $builder, $post ) {
	if ( 'unknown' !== $builder ) {
		return $builder;
	}

	return get_post_meta( $post->ID, '_et_builder_version', true ) ? 'divi' : $builder;
}

add_filter( 'nab_content_builder', __NAMESPACE__ . '\infer_builder', 10, 2 );
