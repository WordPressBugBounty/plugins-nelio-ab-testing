<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for loading headline alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<THeadline_Control_Attributes,THeadline_Alternative_Attributes>
 */
class Alternative_Headline_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	public function init() {
		add_filter( 'the_title', array( $this, 'maybe_replace_title' ), 10, 2 );
		add_filter( 'get_the_excerpt', array( $this, 'maybe_replace_excerpt' ), 10, 2 );
		add_filter( 'get_post_metadata', array( $this, 'maybe_replace_featured_image' ), 10, 3 );
	}

	/**
	 * Callback to replace post title.
	 *
	 * @param string $title   Post title.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 */
	public function maybe_replace_title( $title, $post_id ) {
		if ( $post_id !== $this->control['postId'] ) {
			return $title;
		}
		if ( empty( $this->alternative['name'] ) ) {
			return $title;
		}
		return $this->alternative['name'];
	}

	/**
	 * Callback to replace post excerpt.
	 *
	 * @param string   $excerpt Post excerpt.
	 * @param \WP_Post $post    Post.
	 *
	 * @return string
	 */
	public function maybe_replace_excerpt( $excerpt, $post ) {
		if ( $post->ID !== $this->control['postId'] ) {
			return $excerpt;
		}
		if ( empty( $this->alternative['excerpt'] ) ) {
			return $excerpt;
		}
		return $this->alternative['excerpt'];
	}

	/**
	 * Callback to replace featured image.
	 *
	 * @param mixed  $value Value.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key Meta key.
	 *
	 * @return mixed
	 */
	public function maybe_replace_featured_image( $value, $object_id, $meta_key ) {
		if ( '_thumbnail_id' !== $meta_key ) {
			return $value;
		}
		if ( $object_id !== $this->control['postId'] ) {
			return $value;
		}

		$image_id = absint( $this->alternative['imageId'] ?? 0 );
		if ( empty( $image_id ) ) {
			return $value;
		}
		return $image_id;
	}
}
