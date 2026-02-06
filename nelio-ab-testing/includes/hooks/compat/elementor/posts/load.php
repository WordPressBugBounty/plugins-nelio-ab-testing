<?php

namespace Nelio_AB_Testing\Compat\Elementor\Posts;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

add_action(
	'plugins_loaded',
	function () {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );

		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );

		add_filter( 'nab_is_tested_post_by_nab/custom-post-type_experiment', __NAMESPACE__ . '\fix_issue_with_elementor_landing_pages', 10, 4 );
	}
);

/**
 * Callback to customize hooks and thus properly load an elementor alternative.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes                              $control     Alternative.
 *
 * @return void
 */
function load_elementor_alternative( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}

	if ( ! get_post_meta( $control['postId'], '_elementor_edit_mode', true ) ) {
		return;
	}

	remove_action( 'nab_nab/page_load_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10 );
	remove_action( 'nab_nab/post_load_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10 );
	remove_action( 'nab_nab/custom-post-type_load_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10 );

	remove_action( 'nab_nab/page_preview_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10 );
	remove_action( 'nab_nab/post_preview_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10 );
	remove_action( 'nab_nab/custom-post-type_preview_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10 );

	$replace_post_results = function ( $posts ) use ( &$replace_post_results, $alternative, $control ) {
		/** @var list<\WP_Post> $posts */

		return array_map(
			function ( $post ) use ( &$replace_post_results, $alternative, $control ) {
				/** @var \WP_Query|null $wp_query */
				global $wp_query;

				if ( $post->ID !== $control['postId'] ) {
					return $post;
				}

				remove_filter( 'posts_results', $replace_post_results );
				remove_filter( 'get_pages', $replace_post_results );
				$alternative_post = get_post( $alternative['postId'] );
				if ( ! empty( $alternative_post ) ) {
					$post              = $alternative_post;
					$post->post_status = 'publish';
					if ( is_singular() && is_main_query() && ! empty( $wp_query ) && $wp_query->queried_object_id === $control['postId'] ) {
						$wp_query->queried_object    = $post;
						$wp_query->queried_object_id = $post->ID;
					}
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
}

/**
 * Fixes an issue with Elementor landing pages.
 *
 * @param bool        $tested  Whether the current request is a single post that’s tested by the given experiment.
 * @param TIgnore     $post_id Post ID of the current post (if any).
 * @param TAttributes $control Original version.
 * @param int         $exp_id  ID of the experiment.
 *
 * @return bool
 */
function fix_issue_with_elementor_landing_pages( $tested, $post_id, $control, $exp_id ) {
	$type = 'e-landing-page';
	if ( $type !== $control['postType'] ) {
		return $tested;
	}

	$name = get_query_var( 'category_name' );
	if ( empty( $name ) ) {
		$name = get_query_var( 'name' );
	}

	if ( empty( $name ) || ! is_string( $name ) ) {
		return $tested;
	}

	/** @var \wpdb $wpdb */
	global $wpdb;
	$key = "nab/$type/$name";
	$id  = wp_cache_get( $key );
	if ( empty( $id ) ) {
		$id = absint(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT ID FROM %i p WHERE p.post_type = %s AND p.post_name = %s',
					$wpdb->posts,
					$type,
					$name
				)
			)
		);
		wp_cache_set( $key, $id );
	}

	if ( empty( $control['testAgainstExistingContent'] ) ) {
		return $id === $control['postId'];
	}

	$experiment = nab_get_experiment( $exp_id );
	if ( is_wp_error( $experiment ) ) {
		return $tested;
	}

	$alts = $experiment->get_alternatives();
	$pids = array_map( fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ), $alts );
	$pids = array_values( array_filter( $pids ) );
	return in_array( $id, $pids, true );
}
