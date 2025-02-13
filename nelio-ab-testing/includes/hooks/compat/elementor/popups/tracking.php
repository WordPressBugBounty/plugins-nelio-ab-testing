<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;

function maybe_add_tracking_script() {
	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, __NAMESPACE__ . '\is_testing_elementor_popup' );
	if ( empty( $experiments ) ) {
		return;
	}//end if

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
}//end maybe_add_tracking_script()
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_add_tracking_script', 99 );
