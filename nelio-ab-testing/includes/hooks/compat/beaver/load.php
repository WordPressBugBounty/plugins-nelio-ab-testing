<?php

namespace Nelio_AB_Testing\Compat\Beaver;

defined( 'ABSPATH' ) || exit;

use FLBuilderModel;

use function add_action;
use function add_filter;
use function class_exists;

/**
 * Callback to use alternative ID during beaver render.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes                              $control     Alternative.
 *
 * @return void
 */
function use_alternative_id_during_beaver_render( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	add_filter( 'fl_builder_render_assets_inline', '__return_true' );

	add_action(
		'wp_enqueue_scripts',
		function () use ( $control_id, $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $control_id ) {
				FLBuilderModel::set_post_id( $alternative_id );
			}
		},
		1
	);

	add_action(
		'wp_enqueue_scripts',
		function () use ( $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $alternative_id ) {
				FLBuilderModel::reset_post_id();
			}
		},
		99
	);

	add_action(
		'fl_builder_render_content_start',
		function () use ( $control_id, $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $control_id ) {
				FLBuilderModel::set_post_id( $alternative_id );
			}
		}
	);

	add_action(
		'fl_builder_render_content_complete',
		function () use ( $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $alternative_id ) {
				FLBuilderModel::reset_post_id();
			}
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'FLBuilderModel' ) ) {
			return;
		}
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
	}
);
