<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading post alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TPost_Control_Attributes,TPost_Alternative_Attributes>
 */
class Alternative_Post_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'nab_alternative_urls', array( $this, 'filter_alternative_urls' ) );

		$is_control = $this->alternative['postId'] === $this->control['postId'];
		if ( $is_control ) {
			return;
		}

		add_filter( 'pre_option_page_on_front', array( $this, 'maybe_replace_front_page_id' ) );

		add_filter( 'posts_results', array( $this, 'replace_tested_post' ) );
		add_filter( 'get_pages', array( $this, 'replace_tested_post' ) );

		add_filter( 'single_post_title', array( $this, 'replace_post_title' ), 10, 2 );
		add_filter( 'the_title', array( $this, 'replace_post_title' ), 10, 2 );
		add_filter( 'nav_menu_item_title', array( $this, 'maybe_replace_menu_item_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'replace_the_content' ), 11 );
		add_filter( 'get_the_excerpt', array( $this, 'maybe_replace_the_excerpt' ), 11 );

		add_filter( 'post_link', array( $this, 'replace_link' ), 10, 2 );
		add_filter( 'page_link', array( $this, 'replace_link' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'replace_link' ), 10, 2 );

		add_filter( 'get_post_metadata', array( $this, 'replace_get_post_metadata' ), 1, 4 );
		add_filter( 'get_object_terms', array( $this, 'replace_get_object_terms' ), 10, 4 );
		add_filter( 'page_template', array( $this, 'maybe_use_front_page_template' ) );
	}

	/**
	 * Callback to possibly replace alternative URLs, if alternative page is home page.
	 *
	 * @param list<string> $urls URLs.
	 *
	 * @return list<string>
	 */
	public function filter_alternative_urls( $urls ) {
		$front_page_id = $this->get_front_page_id();
		if ( $this->alternative['postId'] !== $front_page_id ) {
			return $urls;
		}
		return array( nab_home_url() );
	}

	/**
	 * Callback to possibly replace front page ID, if front page is the tested post.
	 *
	 * @param mixed $res Result.
	 *
	 * @return mixed
	 */
	public function maybe_replace_front_page_id( $res ) {
		remove_filter( 'pre_option_page_on_front', array( $this, 'maybe_replace_front_page_id' ) );
		$front_page = $this->get_front_page_id();
		add_filter( 'pre_option_page_on_front', array( $this, 'maybe_replace_front_page_id' ) );
		return $this->control['postId'] === $front_page ? $this->alternative['postId'] : $res;
	}

	/**
	 * Callback to replace tested post.
	 *
	 * @param list<\WP_Post> $posts Posts.
	 *
	 * @return list<\WP_Post>
	 */
	public function replace_tested_post( $posts ) {
		return array_map(
			function ( $post ) {
				if ( $post->ID !== $this->control['postId'] && $post->ID !== $this->alternative['postId'] ) {
					return $post;
				}

				if ( $post->ID === $this->control['postId'] ) {
					remove_filter( 'posts_results', array( $this, 'replace_tested_post' ) );
					remove_filter( 'get_pages', array( $this, 'replace_tested_post' ) );
					$alternative_post = get_post( $this->alternative['postId'] );
					add_filter( 'posts_results', array( $this, 'replace_tested_post' ) );
					add_filter( 'get_pages', array( $this, 'replace_tested_post' ) );
					if ( empty( $alternative_post ) ) {
						return $post;
					}
					$post = $alternative_post;
				}

				$post->post_status = 'publish';

				if ( use_control_id_in_alternative() ) {
					$post->ID = $this->control['postId'];
				}

				/** @var \WP_Query $wp_query */
				global $wp_query;
				if ( is_singular() && is_main_query() && $wp_query->queried_object_id === $this->control['postId'] ) {
					$wp_query->queried_object    = $post;
					$wp_query->queried_object_id = $post->ID;
				}

				return $post;
			},
			$posts
		);
	}

	/**
	 * Callback to replace post title.
	 *
	 * @param string       $post_title Post title.
	 * @param int|\WP_Post $post       Post or post ID.
	 *
	 * @return string
	 */
	public function replace_post_title( $post_title, $post ) {
		$post_id = $post instanceof \WP_Post ? $post->ID : $post;

		if ( $post_id !== $this->control['postId'] ) {
			return $post_title;
		}

		$post = get_post( $this->alternative['postId'] );
		if ( empty( $post ) ) {
			return $post_title;
		}

		return get_the_title( $post );
	}

	/**
	 * Callback to possibly replace menu item title, if none is explicitly set.
	 *
	 * @param string                                     $title Title.
	 * @param object{post_title:string,object_id?:mixed} $item  Menu item.
	 *
	 * @return string
	 */
	public function maybe_replace_menu_item_title( $title, $item ) {
		if ( ! empty( $item->post_title ) ) {
			return $title;
		}

		if ( property_exists( $item, 'object_id' ) && is_numeric( $item->object_id ) && "{$this->control['postId']}" !== "{$item->object_id}" ) {
			return $title;
		}

		$post = get_post( $this->alternative['postId'] );
		if ( empty( $post ) ) {
			return $title;
		}

		return get_the_title( $post );
	}

	/**
	 * Callback to replace the content.
	 *
	 * @param string $content Content.
	 *
	 * @return string
	 */
	public function replace_the_content( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( get_the_ID() !== $this->control['postId'] ) {
			return $content;
		}

		$post = get_post( $this->alternative['postId'] );
		if ( empty( $post ) ) {
			return $content;
		}

		$alt_content = $post->post_content;
		if ( empty( $alt_content ) ) {
			return $content;
		}

		remove_filter( 'the_content', array( $this, 'replace_the_content' ), 11 );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$alt_content = apply_filters( 'the_content', $alt_content );
		$alt_content = is_string( $alt_content ) ? $alt_content : $content;
		add_filter( 'the_content', array( $this, 'replace_the_content' ), 11 );
		return $alt_content;
	}

	/**
	 * Callback to possibly replace the excerpt, if alternative excerpt is not empty.
	 *
	 * @param string $excerpt Excerpt.
	 * @return string
	 */
	public function maybe_replace_the_excerpt( $excerpt ) {
		if ( get_the_ID() !== $this->control['postId'] ) {
			return $excerpt;
		}

		$post = get_post( $this->alternative['postId'] );
		if ( empty( $post ) ) {
			return $excerpt;
		}

		$alt_excerpt = $post->post_excerpt;
		if ( empty( $alt_excerpt ) ) {
			return $excerpt;
		}

		remove_filter( 'get_the_excerpt', array( $this, 'maybe_replace_the_excerpt' ), 11 );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$alt_excerpt = apply_filters( 'get_the_excerpt', $alt_excerpt );
		$alt_excerpt = is_string( $alt_excerpt ) ? $alt_excerpt : $excerpt;
		add_filter( 'get_the_excerpt', array( $this, 'maybe_replace_the_excerpt' ), 11 );
		return $alt_excerpt;
	}

	/**
	 * Callback to replace link.
	 *
	 * @param string       $permalink Permalink.
	 * @param int|\WP_Post $post Post or post ID.
	 *
	 * @return string
	 */
	public function replace_link( $permalink, $post ) {
		$post_id = $post instanceof \WP_Post ? $post->ID : $post;
		$post_id = ! empty( $post_id ) ? $post_id : nab_url_to_postid( $permalink );

		if ( use_control_id_in_alternative() && $post_id === $this->control['postId'] ) {
			remove_filter( 'post_link', array( $this, 'replace_link' ), 10 );
			remove_filter( 'page_link', array( $this, 'replace_link' ), 10 );
			remove_filter( 'post_type_link', array( $this, 'replace_link' ), 10 );
			$control_permalink = get_permalink( $this->control['postId'] );
			add_filter( 'post_link', array( $this, 'replace_link' ), 10, 2 );
			add_filter( 'page_link', array( $this, 'replace_link' ), 10, 2 );
			add_filter( 'post_type_link', array( $this, 'replace_link' ), 10, 2 );
			return is_string( $control_permalink ) ? $control_permalink : $permalink;
		}

		if ( $post_id !== $this->alternative['postId'] ) {
			return $permalink;
		}

		$control_permalink = get_permalink( $this->control['postId'] );
		return is_string( $control_permalink ) ? $control_permalink : $permalink;
	}

	/**
	 * Callback to replace all meta data.
	 *
	 * @param mixed  $value     Value.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether single.
	 *
	 * @return mixed
	 */
	public function replace_get_post_metadata( $value, $object_id, $meta_key, $single ) {
		if ( $object_id !== $this->control['postId'] ) {
			return $value;
		}

		// We always recover the "full" post meta (i.e. $single => false) so that WordPress doesn't "break" things.
		// See https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/meta.php#L514.
		/** @var array<mixed> $value */
		$value = get_post_meta( $this->alternative['postId'], $meta_key, false );
		if ( empty( $value ) && $single ) {
			$value[0] = '';
		}

		return $value;
	}

	/**
	 * Callback to replace object terms.
	 *
	 * @param array<\WP_Term>|array<int>|array<string>|array<string> $terms      Terms.
	 * @param list<int>                                              $object_ids Object IDs.
	 * @param list<string>                                           $taxonomies Taxonomies.
	 * @param array{fields?:string}                                  $args       Arguments.
	 *
	 * @return array<\WP_Term>|array<int>|array<string>|array<string>|array<object{object_id:int}>
	 */
	public function replace_get_object_terms( $terms, $object_ids, $taxonomies, $args ) {
		if ( ! in_array( $this->control['postId'], $object_ids, true ) ) {
			return $terms;
		}

		/**
		 * Gets the taxonomies that can be tested and, therefore, should be replaced during a test.
		 *
		 * @param list<string> $taxonomies list of taxonomies.
		 * @param string       $post_type  the post type for which we're retrieving the list of taxonomies
		 *
		 * @since 5.0.9
		 */
		$taxonomies = apply_filters( 'nab_get_testable_taxonomies', $taxonomies, $this->control['postType'] );

		/** @var list<\WP_Term>|list<int>|list<string>|list<string> */
		$non_testable_terms = array_values(
			array_filter(
				$terms,
				function ( $term ) use ( &$taxonomies ) {
					return ! is_object( $term ) || ! in_array( $term->taxonomy, $taxonomies, true );
				}
			)
		);

		$object_ids   = array_values( array_diff( $object_ids, array( $this->control['postId'] ) ) );
		$object_ids[] = absint( $this->alternative['postId'] );

		$extra_terms = wp_get_object_terms( $object_ids, $taxonomies, $args );
		if ( ! is_wp_error( $extra_terms ) ) {
			$extra_terms = is_array( $extra_terms ) ? $extra_terms : array( $extra_terms );
			$terms       = array_values( array_filter( array_merge( $non_testable_terms, $extra_terms ) ) );
		}

		$terms = array_map(
			function ( $term ) {
				if ( ! is_object( $term ) || ! property_exists( $term, 'object_id' ) ) {
					return $term;
				}

				if ( use_control_id_in_alternative() && $term->object_id === $this->alternative['postId'] ) {
					$term->object_id = $this->control['postId'];
				}

				if ( ! use_control_id_in_alternative() && $term->object_id === $this->control['postId'] ) {
					$term->object_id = $this->alternative['postId'];
				}

				return $term;
			},
			$terms
		);

		return $terms;
	}

	/**
	 * Callback to use front page template on alternative content if control page is front page and there’s a specific template for it.
	 *
	 * @param string $template Template.
	 *
	 * @return string
	 */
	public function maybe_use_front_page_template( $template ) {
		if ( $this->get_front_page_id() !== $this->alternative['postId'] ) {
			return $template;
		}

		if ( 'page' !== get_post_type( $this->alternative['postId'] ) ) {
			return $template;
		}

		$front_page_template = locate_template( 'front-page.php' );
		return $front_page_template ? $front_page_template : $template;
	}

	/**
	 * Gets front page ID.
	 *
	 * @return int
	 */
	private function get_front_page_id() {
		return 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0;
	}
}
