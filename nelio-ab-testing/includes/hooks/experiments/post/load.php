<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Settings;

use function add_action;
use function add_filter;

add_filter( 'nab_nab/page_experiment_priority', fn() => 'mid' );
add_filter( 'nab_nab/post_experiment_priority', fn() => 'mid' );
add_filter( 'nab_nab/custom-post-type_experiment_priority', fn() => 'mid' );

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
 * Callback to add hooks to load alternative content.
 *
 * @param TPost_Alternative_Attributes|TPost_Control_Attributes $alternative   Alternative.
 * @param TPost_Control_Attributes                              $control       Alternative.
 * @param int                                                   $experiment_id Experiment ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id ) {
	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		add_filter(
			'nab_alternative_urls',
			function ( $urls ) use ( $experiment_id ) {
				/** @var list<string> $urls */

				return get_alternative_urls( $urls, $experiment_id );
			}
		);

		if ( ! empty( $control['useControlUrl'] ) ) {
			add_filter( 'nab_use_control_url_in_multi_url_alternative', '__return_true' );
		}

		return;
	}

	add_filter(
		'nab_alternative_urls',
		function ( $urls ) use ( $alternative ) {
			$front_page_id = get_front_page_id();
			if ( $alternative['postId'] !== $front_page_id ) {
				return $urls;
			}
			return array( nab_home_url() );
		}
	);

	if ( is_control( $alternative, $control ) ) {
		return;
	}

	$fix_front_page = function ( $res ) use ( &$fix_front_page, $control, $alternative ) {
		remove_filter( 'pre_option_page_on_front', $fix_front_page );
		$front_page = get_front_page_id();
		add_filter( 'pre_option_page_on_front', $fix_front_page );
		return $control['postId'] === $front_page ? $alternative['postId'] : $res;
	};
	add_filter( 'pre_option_page_on_front', $fix_front_page );

	add_filter(
		'single_post_title',
		function ( $post_title, $post ) use ( $control, $alternative ) {
			/** @var string   $post_title */
			/** @var \WP_Post $post       */

			if ( $post->ID !== $control['postId'] ) {
				return $post_title;
			}

			$post = get_post( $alternative['postId'] );
			if ( empty( $post ) ) {
				return $post_title;
			}

			return get_the_title( $post );
		},
		10,
		2
	);

	$replace_post_results = function ( $posts ) use ( &$replace_post_results, $alternative, $control ) {
		/** @var list<\WP_Post> $posts */

		return array_map(
			function ( $post ) use ( &$replace_post_results, $alternative, $control ) {
				/** @var \WP_Query $wp_query */
				global $wp_query;

				if ( $post->ID === $alternative['postId'] && get_front_page_id() === $alternative['postId'] ) {
					$post->post_status = 'publish';
					if ( use_control_id_in_alternative() ) {
						$post->ID = $control['postId'];
						if ( is_singular() && is_main_query() && $wp_query->queried_object_id === $control['postId'] ) {
							$wp_query->queried_object    = $post;
							$wp_query->queried_object_id = $post->ID;
						}
					}
					return $post;
				}

				if ( $post->ID !== $control['postId'] ) {
					return $post;
				}

				remove_filter( 'posts_results', $replace_post_results );
				remove_filter( 'get_pages', $replace_post_results );
				$alternative_post = get_post( $alternative['postId'] );
				if ( empty( $alternative_post ) ) {
					return $post;
				}

				$post              = $alternative_post;
				$post->post_status = 'publish';

				if ( use_control_id_in_alternative() ) {
					$post->ID = $control['postId'];
				}

				if ( is_singular() && is_main_query() && $wp_query->queried_object_id === $control['postId'] ) {
					$wp_query->queried_object    = $post;
					$wp_query->queried_object_id = $post->ID;
				}

				add_filter( 'posts_results', $replace_post_results );
				add_filter( 'get_pages', $replace_post_results );
				return $post;
			},
			$posts
		);
	};
	add_filter( 'posts_results', $replace_post_results );
	add_filter( 'get_pages', $replace_post_results );

	$fix_title = function ( $title, $post_id ) use ( $alternative, $control ) {
		if ( $post_id !== $control['postId'] ) {
			return $title;
		}

		$post = get_post( $alternative['postId'] );
		if ( ! $post ) {
			return $title;
		}

		return get_the_title( $post );
	};
	add_filter( 'the_title', $fix_title, 10, 2 );

	$fix_content = function ( $content ) use ( &$fix_content, $alternative, $control ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( get_the_ID() !== $control['postId'] ) {
			return $content;
		}

		$post = get_post( $alternative['postId'] );
		if ( empty( $post ) ) {
			return $content;
		}

		$alt_content = $post->post_content;
		if ( empty( $alt_content ) ) {
			return $content;
		}

		remove_filter( 'the_content', $fix_content, 11 );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$alt_content = apply_filters( 'the_content', $alt_content );
		add_filter( 'the_content', $fix_content, 11 );
		return $alt_content;
	};
	add_filter( 'the_content', $fix_content, 11 );

	$fix_excerpt = function ( $excerpt ) use ( &$fix_excerpt, $alternative, $control ) {
		if ( get_the_ID() !== $control['postId'] ) {
			return $excerpt;
		}

		$post = get_post( $alternative['postId'] );
		if ( empty( $post ) ) {
			return $excerpt;
		}

		$alt_excerpt = $post->post_excerpt;
		if ( empty( $alt_excerpt ) ) {
			return $excerpt;
		}

		remove_filter( 'get_the_excerpt', $fix_excerpt, 11 );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$alt_excerpt = apply_filters( 'get_the_excerpt', $alt_excerpt );
		add_filter( 'get_the_excerpt', $fix_excerpt, 11 );
		return $alt_excerpt;
	};
	add_filter( 'get_the_excerpt', $fix_excerpt, 11 );

	$use_alternative_metas = function ( $value, $object_id, $meta_key, $single ) use ( $alternative, $control ) {
		/** @var mixed  $value     */
		/** @var int    $object_id */
		/** @var string $meta_key  */
		/** @var bool   $single    */

		if ( $object_id !== $control['postId'] ) {
			return $value;
		}

		// We always recover the “full” post meta (i.e. $single => false) so that
		// WordPress doesn’t “break” things.
		// See https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/meta.php#L514.
		/** @var array<mixed> $value */
		$value = get_post_meta( $alternative['postId'], $meta_key, false );
		if ( empty( $value ) && $single ) {
			$value[0] = '';
		}

		return $value;
	};
	add_filter( 'get_post_metadata', $use_alternative_metas, 1, 4 );

	add_filter(
		'get_object_terms',
		function ( $terms, $object_ids, $taxonomies, $args ) use ( $alternative, $control ) {
			/** @var list<\WP_Term>|list<int>|list<string>|list<string> $terms      */
			/** @var list<int>                                          $object_ids */
			/** @var list<string>                                       $taxonomies */
			/** @var array{fields?:string}                              $args       */

			if ( ! in_array( $control['postId'], $object_ids, true ) ) {
				return $terms;
			}

			/**
			 * Gets the taxonomies that can be tested and, therefore, should be replaced during a test.
			 *
			 * @param list<string> $taxonomies list of taxonomies.
			 * @param string       $post_type  the post type for which we’re retrieving the list of taxonomies
			 *
			 * @since 5.0.9
			 */
			$taxonomies = apply_filters( 'nab_get_testable_taxonomies', $taxonomies, $control['postType'] );

			/** @var list<\WP_Term>|list<int>|list<string>|list<string> */
			$non_testable_terms = array_values(
				array_filter(
					$terms,
					function ( $term ) use ( &$taxonomies ) {
						return ! is_object( $term ) || ! in_array( $term->taxonomy, $taxonomies, true );
					}
				)
			);

			$object_ids   = array_values( array_diff( $object_ids, array( $control['postId'] ) ) );
			$object_ids[] = absint( $alternative['postId'] );

			$extra_terms = wp_get_object_terms( $object_ids, $taxonomies, $args );
			if ( ! is_wp_error( $extra_terms ) ) {
				$extra_terms = is_array( $extra_terms ) ? $extra_terms : array( $extra_terms );
				$terms       = array_values( array_filter( array_merge( $non_testable_terms, $extra_terms ) ) );
			}

			if ( isset( $args['fields'] ) && 'all_with_object_id' !== $args['fields'] ) {
				return $terms;
			}

			$terms = array_map(
				function ( $term ) use ( $control, $alternative ) {
					if ( ! is_object( $term ) || ! property_exists( $term, 'object_id' ) ) {
						return $term;
					}

					if ( use_control_id_in_alternative() && $term->object_id === $alternative['postId'] ) {
						$term->object_id = $control['postId'];
					}

					if ( ! use_control_id_in_alternative() && $term->object_id === $control['postId'] ) {
						$term->object_id = $alternative['postId'];
					}

					return $term;
				},
				$terms
			);

			return $terms;
		},
		10,
		4
	);

	$use_alt_title_in_menus = function ( $title, $item ) use ( $alternative, $control ) {
		/** @var string   $title */
		/** @var \WP_Post $item  */

		if ( ! empty( $item->post_title ) ) {
			return $title;
		}

		if ( property_exists( $item, 'object_id' ) && is_numeric( $item->object_id ) && "{$control['postId']}" !== "{$item->object_id}" ) {
			return $title;
		}

		$post = get_post( $alternative['postId'] );
		if ( ! $post ) {
			return $title;
		}

		return get_the_title( $post );
	};
	add_filter( 'nav_menu_item_title', $use_alt_title_in_menus, 10, 2 );

	add_filter(
		'page_template',
		function ( $template ) use ( $alternative ) {
			if ( get_front_page_id() !== $alternative['postId'] ) {
				return $template;
			}

			if ( 'page' !== get_post_type( $alternative['postId'] ) ) {
				return $template;
			}

			$front_page_template = locate_template( 'front-page.php' );
			return $front_page_template ? $front_page_template : $template;
		}
	);

	use_control_comments_in_alternative( $control['postId'], $alternative['postId'] );
}
add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

/**
 * Callback to fix alternative link.
 *
 * @param TPost_Alternative_Attributes|TPost_Control_Attributes $alternative   Alternative.
 * @param TPost_Control_Attributes                              $control       Alternative.
 *
 * @return void
 */
function fix_alternative_link( $alternative, $control ) {

	if ( is_control( $alternative, $control ) ) {
		return;
	}

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}

	$fix_link = function ( $permalink, $post ) use ( &$fix_link, $alternative, $control ) {
		/** @var string       $permalink */
		/** @var int|\WP_Post $post      */

		$post = $post instanceof \WP_Post ? $post->ID : $post;
		$post = ! empty( $post ) ? $post : nab_url_to_postid( $permalink );

		if ( use_control_id_in_alternative() && $post === $control['postId'] ) {
			remove_filter( 'post_link', $fix_link, 10 );
			remove_filter( 'page_link', $fix_link, 10 );
			remove_filter( 'post_type_link', $fix_link, 10 );
			$permalink = get_permalink( $control['postId'] );
			add_filter( 'post_link', $fix_link, 10, 2 );
			add_filter( 'page_link', $fix_link, 10, 2 );
			add_filter( 'post_type_link', $fix_link, 10, 2 );
			return $permalink;
		}

		if ( $post !== $alternative['postId'] ) {
			return $permalink;
		}

		return get_permalink( $control['postId'] );
	};
	add_filter( 'post_link', $fix_link, 10, 2 );
	add_filter( 'page_link', $fix_link, 10, 2 );
	add_filter( 'post_type_link', $fix_link, 10, 2 );

	$fix_shortlink = function ( $shortlink, $post_id ) use ( &$fix_shortlink, $alternative, $control ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		if ( use_control_id_in_alternative() && $post_id === $control['postId'] ) {
			remove_filter( 'get_shortlink', $fix_shortlink, 10 );
			$shortlink = wp_get_shortlink( $control['postId'] );
			add_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
			return $shortlink;
		}

		if ( $post_id !== $alternative['postId'] ) {
			return $shortlink;
		}

		return wp_get_shortlink( $control['postId'] );
	};
	add_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
}
add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\fix_alternative_link', 10, 2 );
add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\fix_alternative_link', 10, 2 );
add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\fix_alternative_link', 10, 2 );

/**
 * Callback to determine if the experiment is running on multiple URLs or not.
 *
 * @param bool                         $result     Result.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return bool
 */
function has_multi_url_alternative( $result, $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	return $result || ! empty( $control['attributes']['testAgainstExistingContent'] );
}
add_filter( 'nab_has_nab/page_multi_url_alternative', __NAMESPACE__ . '\has_multi_url_alternative', 10, 2 );
add_filter( 'nab_has_nab/post_multi_url_alternative', __NAMESPACE__ . '\has_multi_url_alternative', 10, 2 );
add_filter( 'nab_has_nab/custom-post-type_multi_url_alternative', __NAMESPACE__ . '\has_multi_url_alternative', 10, 2 );

// ========
// INTERNAL
// ========

/**
 * Gets front page ID.
 *
 * @return int
 */
function get_front_page_id() {
	return 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0;
}

/**
 * Whether the current alternative is the control.
 *
 * @param TPost_Alternative_Attributes|TPost_Control_Attributes $alternative   Alternative.
 * @param TPost_Control_Attributes                              $control       Alternative.
 *
 * @return bool
 *
 * @phpstan-assert-if-true TPost_Control_Attributes $alternative
 */
function is_control( $alternative, $control ) {
	if ( $control['postId'] === $alternative['postId'] ) {
		return true;
	}

	return false;
}

/**
 * Returns the list of alternative URLs.
 *
 * @param list<string> $urls URLs.
 * @param int          $experiment_id Experiment ID.
 *
 * @return list<string>
 */
function get_alternative_urls( $urls, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $urls;
	}
	$alts = $experiment->get_alternatives();
	$alts = array_map( fn( $a )=> absint( $a['attributes']['postId'] ?? 0 ), $alts );
	return array_values( array_filter( array_map( 'get_permalink', $alts ) ) );
}

/**
 * Adds hooks to use control comments in alternative.
 *
 * @param int $control_id     Control ID.
 * @param int $alternative_id Alternative ID.
 *
 * @return void
 */
function use_control_comments_in_alternative( $control_id, $alternative_id ) {
	// Allow comments.
	add_filter(
		'comments_open',
		function ( $result, $post_id ) use ( $control_id, $alternative_id ) {
			if ( $post_id !== $alternative_id ) {
				return $result;
			}
			return comments_open( $control_id );
		},
		10,
		2
	);

	// Show control comments.
	add_filter(
		'comments_template_query_args',
		function ( $query ) use ( $control_id, $alternative_id ) {
			/** @var array<string,mixed> $query */

			if ( $query['post_id'] !== $alternative_id ) {
				return $query;
			}
			return wp_parse_args(
				array( 'post_id' => $control_id ),
				$query
			);
		}
	);

	// Show appropriate comment count.
	add_filter(
		'get_comments_number',
		function ( $count, $post_id ) use ( $control_id, $alternative_id ) {
			/** @var int $count   */
			/** @var int $post_id */

			if ( $post_id !== $alternative_id ) {
				return $count;
			}
			$aux = get_post( $control_id );
			if ( empty( $aux ) ) {
				return $count;
			}

			return $aux->comment_count;
		},
		10,
		2
	);

	// Use control comment form.
	add_filter(
		'comment_id_fields',
		function ( $fields, $post_id, $reply_to_id ) use ( $control_id, $alternative_id ) {
			/** @var string $fields      */
			/** @var int    $post_id     */
			/** @var int    $reply_to_id */

			if ( $post_id !== $alternative_id ) {
				return $fields;
			}
			$fields  = '';
			$fields .= sprintf(
				'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
				esc_attr( 'comment_post_ID' ),
				esc_attr( $control_id )
			);
			$fields .= sprintf(
				'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
				esc_attr( 'comment_parent' ),
				esc_attr( $reply_to_id )
			);
			return $fields;
		},
		10,
		3
	);
}
