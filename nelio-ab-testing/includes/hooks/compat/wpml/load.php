<?php

namespace Nelio_AB_Testing\Compat\WPML;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function Nelio_AB_Testing\Experiment_Library\Post_Experiment\use_control_id_in_alternative;

/**
 * Callback to fix language switcher when loading alternative content.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Attributes of the active alternative.
 * @param TPost_Control_Attributes                              $control     Attributes of the control version.
 *
 * @return void
 */
function fix_language_switcher( $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}

	if ( use_control_id_in_alternative() ) {
		return;
	}

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	add_filter(
		'icl_ls_languages',
		function ( $languages ) use ( $alternative_id, $control_id ) {
			/** @var array<string,mixed> $languages */

			if ( ! nab_get_queried_object_id() ) {
				return $languages;
			}

			if ( nab_get_queried_object_id() === $control_id ) {
				return $languages;
			}

			if ( nab_get_queried_object_id() !== $alternative_id ) {
				return $languages;
			}

			/** @var \SitePress|null $sitepress */
			global $sitepress;
			if ( empty( $sitepress ) ) {
				return $languages;
			}

			// Let's get original's post selector.
			/** @var \WP_Query $wp_query */
			global $wp_query;
			/** @var array<string,int> */
			global $wp_actions;
			$post = get_post( $control_id );
			if ( empty( $post->ID ) ) {
				return $languages;
			}

			// Clone original $wp_query.
			$_wp_query = clone $wp_query;

			// Fix query.
			$wp_query->queried_object_id = $control_id;
			$wp_query->queried_object    = $post;
			$wp_query->post              = $post;
			$wp_action_count             = $wp_actions['wp'];
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_actions['wp'] = 0;

			$sitepress->set_wp_query();
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_actions['wp'] = $wp_action_count;
			$languages        = $sitepress->get_ls_languages();

			// Restore $wp_query.
			unset( $wp_query );
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_query = clone $_wp_query;
			unset( $_wp_query );
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_actions['wp'] = 0;
			$sitepress->set_wp_query();
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_actions['wp'] = $wp_action_count;

			return $languages;
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}

		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\fix_language_switcher', 1, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\fix_language_switcher', 1, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\fix_language_switcher', 1, 2 );
	}
);
