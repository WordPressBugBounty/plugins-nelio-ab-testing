<?php

namespace Nelio_AB_Testing\Compat\Beaver;

defined( 'ABSPATH' ) || exit;

use FLBuilderModel;

use function add_action;
use function add_filter;
use function class_exists;

function use_alternative_id_during_beaver_render( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	add_filter( 'fl_builder_render_assets_inline', '__return_true' );

	add_action(
		'wp_enqueue_scripts',
		function () use ( $control_id, $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $control_id ) {
				FLBuilderModel::set_post_id( $alternative_id );
			}//end if
		},
		1
	);

	add_action(
		'wp_enqueue_scripts',
		function () use ( $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $alternative_id ) {
				FLBuilderModel::reset_post_id();
			}//end if
		},
		99
	);

	add_action(
		'fl_builder_render_content_start',
		function () use ( $control_id, $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $control_id ) {
				FLBuilderModel::set_post_id( $alternative_id );
			}//end if
		}
	);

	add_action(
		'fl_builder_render_content_complete',
		function () use ( $alternative_id ) {
			if ( FLBuilderModel::get_post_id() === $alternative_id ) {
				FLBuilderModel::reset_post_id();
			}//end if
		}
	);
}//end use_alternative_id_during_beaver_render()

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'FLBuilderModel' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
	}
);
