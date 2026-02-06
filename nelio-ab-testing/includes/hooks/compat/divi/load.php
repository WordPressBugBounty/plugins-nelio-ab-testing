<?php

namespace Nelio_AB_Testing\Compat\Divi;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Callback to disable control ID in alternative content.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes                              $control     Alternative.
 *
 * @return void
 */
function load_alternative_content( $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	if ( $control_id === $alternative_id ) {
		return;
	}

	if ( empty( get_post_meta( $control_id, '_et_builder_version', true ) ) ) {
		return;
	}

	add_filter( 'nab_use_control_id_in_alternative', '__return_false' );
}

add_action(
	'plugins_loaded',
	function () {
		// Notice: these hooks must be enabled ALWAYS, because during `plugins_loaded`
		// we can't check if Divi theme is active and, if it is, we need them.
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
	}
);
