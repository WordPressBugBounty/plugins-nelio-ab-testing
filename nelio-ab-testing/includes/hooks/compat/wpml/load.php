<?php

namespace Nelio_AB_Testing\Compat\WPML;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function Nelio_AB_Testing\Experiment_Library\Post_Experiment\use_control_id_in_alternative;

function fix_language_switcher( $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	if ( use_control_id_in_alternative() ) {
		return;
	}//end if

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	add_filter(
		'icl_ls_languages',
		function ( $languages ) use ( $alternative_id, $control_id ) {
			if ( ! nab_get_queried_object_id() ) {
				return $languages;
			}//end if

			if ( nab_get_queried_object_id() === $control_id ) {
				return $languages;
			}//end if

			if ( nab_get_queried_object_id() !== $alternative_id ) {
				return $languages;
			}//end if

			global $sitepress;
			if (
				! method_exists( $sitepress, 'set_wp_query' ) ||
				! method_exists( $sitepress, 'get_ls_languages' )
			) {
				return $languages;
			}//end if

			// Let's get original's post selector.
			global $wp_query, $wp_actions;
			$post = get_post( $control_id );
			if ( empty( $post->ID ) ) {
				return $languages;
			}//end if

			// Clone original $wp_query.
			$_wp_query = clone $wp_query;

			// Fix query.
			$wp_query->queried_object_id = $control_id;
			$wp_query->queried_object    = $post;
			$wp_query->post              = $post;
			$wp_action_count             = $wp_actions['wp'];
			$wp_actions['wp']            = 0; // phpcs:ignore

			$sitepress->set_wp_query();
			$wp_actions['wp'] = $wp_action_count; // phpcs:ignore
			$languages        = $sitepress->get_ls_languages();

			// Restore $wp_query.
			unset( $wp_query );
			$wp_query = clone $_wp_query; // phpcs:ignore
			unset( $_wp_query );
			$wp_actions['wp'] = 0; // phpcs:ignore
			$sitepress->set_wp_query();
			$wp_actions['wp'] = $wp_action_count; // phpcs:ignore

			return $languages;
		}
	);
}//end fix_language_switcher()

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}//end if

		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\fix_language_switcher', 1, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\fix_language_switcher', 1, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\fix_language_switcher', 1, 2 );
	}
);
