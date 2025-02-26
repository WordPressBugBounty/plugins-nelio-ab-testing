<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Settings;

use function add_action;
use function add_filter;

add_filter( 'nab_nab/page_experiment_priority', fn() => 'mid' );
add_filter( 'nab_nab/post_experiment_priority', fn() => 'mid' );
add_filter( 'nab_nab/custom-post-type_experiment_priority', fn() => 'mid' );

function use_control_id_in_alternative() {
	$settings       = Nelio_AB_Testing_Settings::instance();
	$use_control_id = $settings->get( 'use_control_id_in_alternative' );

	/**
	 * Whether we should use the original post ID when loading an alternative post or not.
	 *
	 * @param boolean $use_control_id whether we should use the original post ID or not.
	 *
	 * @since 5.0.4
	 */
	return apply_filters( 'nab_use_control_id_in_alternative', $use_control_id );
}//end use_control_id_in_alternative()

function load_alternative( $alternative, $control, $experiment_id ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		add_filter(
			'nab_alternative_urls',
			fn( $urls ) => get_alternative_urls( $urls, $experiment_id )
		);
		return;
	}//end if

	if ( skip_hooks( $alternative, $control, $experiment_id ) ) {
		return;
	}//end if

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
			if ( $post->ID !== $control['postId'] ) {
				return $post_title;
			}//end if
			$post = get_post( $alternative['postId'] );
			return get_the_title( $post );
		},
		10,
		2
	);

	$replace_post_results = function ( $posts ) use ( &$replace_post_results, $alternative, $control ) {

		return array_map(
			function ( $post ) use ( &$replace_post_results, $alternative, $control ) {
				global $wp_query;

				if ( $post->ID === $alternative['postId'] && get_front_page_id() === $alternative['postId'] ) {
					$post->post_status = 'publish';
					if ( use_control_id_in_alternative() ) {
						$post->ID = $control['postId'];
						if ( is_singular() && is_main_query() ) {
							$wp_query->queried_object    = $post;
							$wp_query->queried_object_id = $post->ID;
						}//end if
					}//end if
					return $post;
				}//end if

				if ( $post->ID !== $control['postId'] ) {
					return $post;
				}//end if

				remove_filter( 'posts_results', $replace_post_results );
				remove_filter( 'get_pages', $replace_post_results );
				$post              = get_post( $alternative['postId'] );
				$post->post_status = 'publish';

				if ( use_control_id_in_alternative() ) {
					$post->ID = $control['postId'];
				}//end if

				if ( is_singular() && is_main_query() ) {
					$wp_query->queried_object    = $post;
					$wp_query->queried_object_id = $post->ID;
				}//end if

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
		}//end if

		$post = get_post( $alternative['postId'] );
		if ( ! $post ) {
			return $title;
		}//end if

		return get_the_title( $post );
	};
	add_filter( 'the_title', $fix_title, 10, 2 );

	$fix_content = function ( $content ) use ( &$fix_content, $alternative, $control ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}//end if

		if ( get_the_ID() !== $control['postId'] ) {
			return $content;
		}//end if

		$post        = get_post( $alternative['postId'], ARRAY_A );
		$alt_content = nab_array_get( $post, 'post_content', '' );
		if ( empty( $alt_content ) ) {
			return $content;
		}//end if

		remove_filter( 'the_content', $fix_content, 11 );
		$alt_content = apply_filters( 'the_content', $alt_content );
		add_filter( 'the_content', $fix_content, 11 );
		return $alt_content;
	};
	add_filter( 'the_content', $fix_content, 11 );

	$fix_excerpt = function ( $excerpt ) use ( &$fix_excerpt, $alternative, $control ) {
		if ( get_the_ID() !== $control['postId'] ) {
			return $excerpt;
		}//end if

		$post        = get_post( $alternative['postId'], ARRAY_A );
		$alt_excerpt = nab_array_get( $post, 'post_excerpt', '' );
		if ( empty( $alt_excerpt ) ) {
			return $excerpt;
		}//end if

		remove_filter( 'the_excerpt', $fix_excerpt, 11 );
		$alt_excerpt = apply_filters( 'the_excerpt', $alt_excerpt );
		add_filter( 'the_excerpt', $fix_excerpt, 11 );
		return $alt_excerpt;
	};
	add_filter( 'the_excerpt', $fix_excerpt );

	$use_alternative_metas = function ( $value, $object_id, $meta_key, $single ) use ( $alternative, $control ) {
		if ( $object_id !== $control['postId'] ) {
			return $value;
		}//end if

		// We always recover the “full” post meta (i.e. $single => false) so that
		// WordPress doesn’t “break” things.
		// See https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/meta.php#L514.
		$value = get_post_meta( $alternative['postId'], $meta_key, false );
		if ( empty( $value ) && $single ) {
			$value[0] = '';
		}//end if

		return $value;
	};
	add_filter( 'get_post_metadata', $use_alternative_metas, 1, 4 );

	add_filter(
		'get_object_terms',
		function ( $terms, $object_ids, $taxonomies, $args ) use ( $alternative, $control ) {
			if ( ! in_array( $control['postId'], $object_ids, true ) ) {
				return $terms;
			}//end if

			/**
			 * Gets the taxonomies that can be tested and, therefore, should be replaced during a test.
			 *
			 * @param array  $taxonomies list of taxonomies.
			 * @param string $post_type  the post type for which we’re retrieving the list of taxonomies
			 *
			 * @since 5.0.9
			 */
			$taxonomies = apply_filters( 'nab_get_testable_taxonomies', $taxonomies, $control['postType'] );

			$non_testable_terms = array_values(
				array_filter(
					$terms,
					function ( $term ) use ( &$taxonomies ) {
						return ! is_object( $term ) || ! in_array( $term->taxonomy, $taxonomies, true );
					}
				)
			);

			$object_ids   = array_diff( $object_ids, array( $control['postId'] ) );
			$object_ids[] = $alternative['postId'];

			$terms = array_values(
				array_merge(
					$non_testable_terms,
					wp_get_object_terms( $object_ids, $taxonomies, $args )
				)
			);

			if ( isset( $args['fields'] ) && 'all_with_object_id' !== $args['fields'] ) {
				return $terms;
			}//end if

			$terms = array_map(
				function ( $term ) use ( $control, $alternative ) {
					if ( use_control_id_in_alternative() && $term->object_id === $alternative['postId'] ) {
						$term->object_id = $control['postId'];
					}//end if

					if ( ! use_control_id_in_alternative() && $term->object_id === $control['postId'] ) {
						$term->object_id = $alternative['postId'];
					}//end if

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
		if ( ! empty( $item->post_title ) ) {
			return $title;
		}//end if

		if ( "{$control['postId']}" !== "{$item->object_id}" ) {
			return $title;
		}//end if

		$post = get_post( $alternative['postId'] );
		if ( ! $post ) {
			return $title;
		}//end if

		return get_the_title( $post );
	};
	add_filter( 'nav_menu_item_title', $use_alt_title_in_menus, 10, 2 );

	add_filter(
		'page_template',
		function ( $template ) use ( $alternative ) {
			if ( get_front_page_id() !== $alternative['postId'] ) {
				return $template;
			}//end if

			if ( 'page' !== get_post_type( $alternative['postId'] ) ) {
				return $template;
			}//end if

			$front_page_template = locate_template( 'front-page.php' );
			return $front_page_template ? $front_page_template : $template;
		}
	);

	use_control_comments_in_alternative( $control['postId'], $alternative['postId'] );
}//end load_alternative()
add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

function fix_alternative_link( $alternative, $control, $experiment_id ) {

	if ( skip_hooks( $alternative, $control, $experiment_id ) ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		add_filter(
			'nab_alternative_urls',
			fn( $urls ) => get_alternative_urls( $urls, $experiment_id )
		);
		return;
	}//end if

	$fix_link = function ( $permalink, $post_id ) use ( &$fix_link, $alternative, $control ) {

		if ( ! is_int( $post_id ) ) {
			if ( is_object( $post_id ) && isset( $post_id->ID ) ) {
				$post_id = $post_id->ID;
			} else {
				$post_id = nab_url_to_postid( $permalink );
			}//end if
		}//end if

		if ( use_control_id_in_alternative() && $post_id === $control['postId'] ) {
			remove_filter( 'post_link', $fix_link, 10, 2 );
			remove_filter( 'page_link', $fix_link, 10, 2 );
			remove_filter( 'post_type_link', $fix_link, 10, 2 );
			$permalink = get_permalink( $control['postId'] );
			add_filter( 'post_link', $fix_link, 10, 2 );
			add_filter( 'page_link', $fix_link, 10, 2 );
			add_filter( 'post_type_link', $fix_link, 10, 2 );
			return $permalink;
		}//end if

		if ( $post_id !== $alternative['postId'] ) {
			return $permalink;
		}//end if

		return get_permalink( $control['postId'] );
	};
	add_filter( 'post_link', $fix_link, 10, 2 );
	add_filter( 'page_link', $fix_link, 10, 2 );
	add_filter( 'post_type_link', $fix_link, 10, 2 );

	$fix_shortlink = function ( $shortlink, $post_id ) use ( &$fix_shortlink, $alternative, $control ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}//end if

		if ( use_control_id_in_alternative() && $post_id === $control['postId'] ) {
			remove_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
			$shortlink = wp_get_shortlink( $control['postId'] );
			add_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
			return $shortlink;
		}//end if

		if ( $post_id !== $alternative['postId'] ) {
			return $shortlink;
		}//end if

		return wp_get_shortlink( $control['postId'] );
	};
	add_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
}//end fix_alternative_link()
add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\fix_alternative_link', 10, 3 );
add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\fix_alternative_link', 10, 3 );
add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\fix_alternative_link', 10, 3 );

function get_inline_settings( $settings, $experiment ) {
	if ( ! is_inline( $experiment ) ) {
		return $settings;
	}//end if
	return array(
		'load' => 'footer',
		'mode' => 'unwrap',
	);
}//end get_inline_settings()
add_filter( 'nab_nab/page_get_inline_settings', __NAMESPACE__ . '\get_inline_settings', 10, 2 );
add_filter( 'nab_nab/post_get_inline_settings', __NAMESPACE__ . '\get_inline_settings', 10, 2 );
add_filter( 'nab_nab/custom-post-type_get_inline_settings', __NAMESPACE__ . '\get_inline_settings', 10, 2 );

function has_multi_url_alternative( $result, $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	return $result || ! empty( $control['attributes']['testAgainstExistingContent'] );
}//end has_multi_url_alternative()
add_filter( 'nab_has_nab/page_multi_url_alternative', __NAMESPACE__ . '\has_multi_url_alternative', 10, 2 );
add_filter( 'nab_has_nab/post_multi_url_alternative', __NAMESPACE__ . '\has_multi_url_alternative', 10, 2 );
add_filter( 'nab_has_nab/custom-post-type_multi_url_alternative', __NAMESPACE__ . '\has_multi_url_alternative', 10, 2 );

// ========
// INTERNAL
// ========

// phpcs:ignore
function is_inline( $experiment ) {
	return (
		! is_wp_error( $experiment ) &&
		'cookie' !== nab_get_variant_loading_strategy() &&
		nab_array_get( $experiment->get_alternatives(), array( 0, 'attributes', 'runAsInlineTest' ), false )
	);
}//end is_inline()

function load_inline_alternative( $experiment ) {
	$alts = $experiment->get_alternatives();
	$alts = wp_list_pluck( $alts, 'attributes' );
	$alts = wp_list_pluck( $alts, 'postId' );
	$alts = array_map( 'get_post', $alts );
	unset( $alts[0] );
	$alts = array_values( $alts );

	$exp_id    = $experiment->get_id();
	$tested_id = nab_array_get( $experiment->get_alternatives(), array( 0, 'attributes', 'postId' ), 0 );

	$doing_content_index = false;

	$replace_title = function ( $title, $post_id ) use ( $exp_id, $tested_id, &$alts, &$replace_title, &$doing_content_index ) {
		if ( $tested_id !== $post_id ) {
			return $title;
		}//end if

		$titles = wp_list_pluck( $alts, 'post_title' );
		remove_filter( 'the_title', $replace_title, 10, 2 );
		$titles = array_map(
			function ( $title ) use ( $post_id ) {
				return apply_filters( 'the_title', $title, $post_id );
			},
			$titles
		);
		$titles = array_merge( array( $title ), $titles );
		add_filter( 'the_title', $replace_title, 10, 2 );

		if ( false !== $doing_content_index ) {
			return isset( $titles[ $doing_content_index ] ) ? $titles[ $doing_content_index ] : $title;
		}//end if

		$titles = array_map( wrap_inline_alternative( $exp_id ), array_keys( $titles ), $titles );
		return implode( '', $titles );
	};
	add_filter( 'the_title', $replace_title, 10, 2 );

	$replace_content = function ( $content ) use ( $exp_id, $tested_id, &$alts, &$replace_content, &$doing_content_index ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}//end if

		if ( get_the_ID() !== $tested_id ) {
			return $content;
		}//end if

		global $post;
		$original = empty( $post ) || $post->ID !== $tested_id ? get_post( $tested_id ) : $post;
		$content  = $original->post_content;

		remove_filter( 'the_content', $replace_content );
		$contents = array_merge( array( $content ), wp_list_pluck( $alts, 'post_content' ) );
		$contents = array_map(
			function ( $index, $content ) use ( &$doing_content_index ) {
				$doing_content_index = $index;
				return apply_filters( 'the_content', $content );
			},
			array_keys( $contents ),
			$contents
		);
		$contents = array_map( wrap_inline_alternative( $exp_id ), array_keys( $contents ), $contents );
		add_filter( 'the_content', $replace_content );

		return implode( '', $contents );
	};
	add_filter( 'the_content', $replace_content );

	$replace_excerpt = function ( $excerpt ) use ( $exp_id, $tested_id, &$alts, &$replace_excerpt ) {
		if ( get_the_ID() !== $tested_id ) {
			return $excerpt;
		}//end if

		$excerpts = wp_list_pluck( $alts, 'post_excerpt' );
		remove_filter( 'the_excerpt', $replace_excerpt );
		$excerpts = array_map(
			function ( $excerpt ) {
				return apply_filters( 'the_excerpt', $excerpt );
			},
			$excerpts
		);
		add_filter( 'the_excerpt', $replace_excerpt );
		$excerpts = array_merge( array( $excerpts ), $excerpts );
		$excerpts = array_map( wrap_inline_alternative( $exp_id ), array_keys( $excerpts ), $excerpts );
		return implode( '', $excerpts );
	};
	add_filter( 'the_excerpt', $replace_excerpt );
}//end load_inline_alternative()

function wrap_inline_alternative( $exp_id ) {
	return function ( $alt_id, $value ) use ( $exp_id ) {
		return sprintf(
			'<div class="nab-exp-%d nab-alt-%d"%s>%s</div>',
			$exp_id,
			$alt_id,
			empty( $alt_id ) ? '' : ' style="display:none"',
			$value
		);
	};
}//end wrap_inline_alternative()

function get_front_page_id() {
	return 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0;
}//end get_front_page_id()

function skip_hooks( $alternative, $control, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( is_inline( $experiment ) ) {
		load_inline_alternative( $experiment );
		return true;
	}//end if

	if ( $control['postId'] === $alternative['postId'] ) {
		return true;
	}//end if

	return false;
}//end skip_hooks()

function get_alternative_urls( $urls, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( empty( $experiment ) ) {
		return $urls;
	}//end if
	$alts = $experiment->get_alternatives();
	$alts = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
	return array_map( 'get_permalink', $alts );
}//end get_alternative_urls()

function use_control_comments_in_alternative( $control_id, $alternative_id ) {
	// Allow comments.
	add_filter(
		'comments_open',
		function ( $result, $post_id ) use ( $control_id, $alternative_id ) {
			if ( $post_id !== $alternative_id ) {
				return $result;
			}//end if
			return comments_open( $control_id );
		},
		10,
		2
	);

	// Show control comments.
	add_filter(
		'comments_template_query_args',
		function ( $query ) use ( $control_id, $alternative_id ) {
			if ( $query['post_id'] !== $alternative_id ) {
				return $query;
			}//end if
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
			if ( $post_id !== $alternative_id ) {
				return $count;
			}//end if
			$aux = get_post( $control_id );
			return $aux->comment_count;
		},
		10,
		2
	);

	// Use control comment form.
	add_filter(
		'comment_id_fields',
		function ( $fields, $post_id, $reply_to_id ) use ( $control_id, $alternative_id ) {
			if ( $post_id !== $alternative_id ) {
				return $fields;
			}//end if
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
}//end use_control_comments_in_alternative()
