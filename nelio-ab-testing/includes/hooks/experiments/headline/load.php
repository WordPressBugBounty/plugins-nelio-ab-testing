<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function load_alternative( $alternative, $control, $experiment_id, $alternative_id ) {

	if ( isset( $alternative['postId'] ) && $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	add_filter(
		'the_title',
		function ( $title, $post_id ) use ( $alternative, $control ) {
			if ( $post_id !== $control['postId'] ) {
				return $title;
			}//end if
			if ( empty( $alternative['name'] ) ) {
				return $title;
			}//end if
			return $alternative['name'];
		},
		10,
		2
	);

	add_filter(
		'get_the_excerpt',
		function ( $excerpt, $post ) use ( $alternative, $control ) {
			if ( $post->ID !== $control['postId'] ) {
				return $excerpt;
			}//end if
			if ( empty( $alternative['excerpt'] ) ) {
				return $excerpt;
			}//end if
			return $alternative['excerpt'];
		},
		10,
		2
	);

	add_filter(
		'get_post_metadata',
		function ( $value, $object_id, $meta_key ) use ( $alternative, $control, $alternative_id ) {
			if ( '_thumbnail_id' !== $meta_key ) {
				return $value;
			}//end if
			if ( $object_id !== $control['postId'] ) {
				return $value;
			}//end if
			if ( empty( $alternative['imageId'] ) && 'control_backup' !== $alternative_id ) {
				return $value;
			}//end if
			return $alternative['imageId'];
		},
		10,
		3
	);
}//end load_alternative()
add_action( 'nab_nab/headline_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 4 );
