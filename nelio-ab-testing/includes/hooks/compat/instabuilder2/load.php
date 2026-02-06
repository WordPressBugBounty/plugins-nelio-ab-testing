<?php

namespace Nelio_AB_Testing\Compat\Instabuilder2;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

use function ib2_page_exists;

/**
 * Loads alternative content.
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

	if ( ! ib2_page_exists( $control_id ) ) {
		return;
	}

	add_filter( 'nab_use_control_id_in_alternative', '__return_false' );
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'IB2_VERSION' ) ) {
			return;
		}
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
	}
);
