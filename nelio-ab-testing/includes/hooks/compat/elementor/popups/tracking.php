<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;

/**
 * Adds tracking script to send a view when a tested popups has opened.
 *
 * @return void
 */
function maybe_add_tracking_script() {
	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, __NAMESPACE__ . '\is_testing_elementor_popup' );
	if ( empty( $experiments ) ) {
		return;
	}

	$script = "
	jQuery( document ).on( 'elementor/popup/show', ( _, popupId ) => {
		window
			?.nabSettings
			?.experiments
			?.filter( ( e ) => (
				e.active &&
				e.type === 'nab/popup' &&
				'nab_elementor_popup' === e.alternatives[0]?.postType &&
				e.alternatives.some( ( a ) => a.postId === popupId )
			) )
			?.forEach( ( exp ) =>
				nab?.view( exp.id )
			);
	} );
	";
	wp_add_inline_script( 'jquery', $script, 'after' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_add_tracking_script', 99 );
