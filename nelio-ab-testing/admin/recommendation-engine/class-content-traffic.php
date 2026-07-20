<?php

namespace Nelio_AB_Testing\Recommendation_Engine;

final class Content_Traffic {

	/** @var string */
	private $post_type;

	/** @var int */
	private $post_id;

	/** @var string */
	private $url;

	/** @var int */
	private $views;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post ID.
	 * @param string $url       Public URL.
	 * @param int    $views     Number of views.
	 *
	 * @return void
	 */
	public function __construct( $post_type, $post_id, $url, $views ) {
		$this->post_type = $post_type;
		$this->post_id   = $post_id;
		$this->url       = $url;
		$this->views     = $views;
	}

	/**
	 * Returns the post type.
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Returns the post ID.
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Returns the URL.
	 *
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Returns the number of views.
	 *
	 * @return int
	 */
	public function get_views() {
		return $this->views;
	}
}
