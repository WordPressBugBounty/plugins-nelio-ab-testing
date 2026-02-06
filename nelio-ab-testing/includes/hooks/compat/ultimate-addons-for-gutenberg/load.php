<?php

namespace Nelio_AB_Testing\Compat\UAGB;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function class_exists;

/**
 * Loads the appropriate styles in variants.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes                              $control     Alternative.
 *
 * @return void
 */
function load_styles( $alternative, $control ) {
	$alternative_id = $alternative['postId'];
	$control_id     = $control['postId'];
	if ( $control_id === $alternative_id ) {
		return;
	}

	add_action(
		'wp_enqueue_scripts',
		function () use ( $alternative_id ) {
			if ( ! class_exists( 'UAGB_Post_Assets' ) ) {
				return;
			}
			$post_assets_instance = new \UAGB_Post_Assets( $alternative_id );
			$post_assets_instance->enqueue_scripts();
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'UAGB_FILE' ) ) {
			return;
		}
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
	}
);
