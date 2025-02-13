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
		}//end if

		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );

		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\load_elementor_alternative', 5, 2 );

		fix_issue_with_elementor_landing_pages();
	}
);

function load_elementor_alternative( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	if ( ! get_post_meta( $control['postId'], '_elementor_edit_mode', true ) ) {
		return;
	}//end if

	remove_action( 'nab_nab/page_load_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10, 3 );
	remove_action( 'nab_nab/post_load_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10, 3 );
	remove_action( 'nab_nab/custom-post-type_load_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10, 3 );

	remove_action( 'nab_nab/page_preview_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10, 3 );
	remove_action( 'nab_nab/post_preview_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10, 3 );
	remove_action( 'nab_nab/custom-post-type_preview_alternative', 'Nelio_AB_Testing\Experiment_Library\Post_Experiment\load_alternative', 10, 3 );

	$replace_post_results = function ( $posts ) use ( &$replace_post_results, $alternative, $control ) {

		return array_map(
			function ( $post ) use ( &$replace_post_results, $alternative, $control ) {
				global $wp_query;

				if ( $post->ID !== $control['postId'] ) {
					return $post;
				}//end if

				remove_filter( 'posts_results', $replace_post_results );
				remove_filter( 'get_pages', $replace_post_results );
				$post              = get_post( $alternative['postId'] );
				$post->post_status = 'publish';
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
}//end load_elementor_alternative()

function fix_issue_with_elementor_landing_pages() {
	add_filter(
		'nab_is_tested_post_by_nab/custom-post-type_experiment',
		function ( $tested, $post_id, $control, $experiment_id ) {
			$type = 'e-landing-page';
			if ( $type !== $control['postType'] ) {
				return $tested;
			}//end if

			$name = get_query_var( 'category_name' );
			if ( empty( $name ) ) {
				return $tested;
			}//end if

			global $wpdb;
			$key = "nab/$type/$name";
			$id  = wp_cache_get( $key );
			if ( empty( $id ) ) {
				$id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts p WHERE p.post_type = %s AND p.post_name = %s", array( $type, $name ) ) ) ); // phpcs:ignore
				wp_cache_set( $key, $id );
			}//end if

			if ( empty( $control['testAgainstExistingContent'] ) ) {
				return $id === $control['postId'];
			}//end if

			$experiment = nab_get_experiment( $experiment_id );
			$alts       = $experiment->get_alternatives();
			$pids       = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
			$pids       = array_values( array_filter( $pids ) );
			return in_array( $id, $pids, true );
		},
		10,
		4
	);
}//end fix_issue_with_elementor_landing_pages()
