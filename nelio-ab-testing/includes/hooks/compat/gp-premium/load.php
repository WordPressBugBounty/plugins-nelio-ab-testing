<?php

namespace Nelio_AB_Testing\Compat\GeneratePress_Premium;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function defined;

/**
 * Callback to add a filter to generate proper dynamic source ID.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes                              $control     Alternative.
 *
 * @return void
 */
function use_proper_source( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}

	add_filter(
		'generate_dynamic_element_source_id',
		function ( $id ) use ( $alternative, $control ) {
			return $id === $control['postId'] ? $alternative['postId'] : $id;
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'GP_PREMIUM_VERSION' ) ) {
			return;
		}
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
	}
);
