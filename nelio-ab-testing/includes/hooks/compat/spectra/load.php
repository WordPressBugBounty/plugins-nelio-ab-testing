<?php

namespace Nelio_AB_Testing\Compat\Spectra;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function class_exists;

function load_styles( $alternative, $control ) {
	$alternative_id = nab_array_get( $alternative, 'postId', 0 );
	$control_id     = nab_array_get( $control, 'postId', 0 );

	if ( $control_id === $alternative_id ) {
		return;
	}//end if

	add_action(
		'wp_enqueue_scripts',
		function () use ( $alternative_id ) {
			if ( ! class_exists( 'UAGB_Post_Assets' ) ) {
				return;
			}//end if
			$post_assets_instance = new \UAGB_Post_Assets( $alternative_id );
			$post_assets_instance->enqueue_scripts();
		}
	);
}//end load_styles()

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'UAGB_FILE' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
	}
);
