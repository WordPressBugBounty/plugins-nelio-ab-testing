<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;

function maybe_add_tracking_script() {
	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, __NAMESPACE__ . '\is_testing_nelio_popup' );
	if ( empty( $experiments ) ) {
		return;
	}//end if

	$script = "
	window.addEventListener( 'nelio-popups/open', ( event ) => {
		window
			?.nabSettings
			?.experiments
			?.filter( ( e ) => (
				e.active &&
				e.type === 'nab/popup' &&
				'nelio_popup' === e.alternatives[0]?.postType &&
				e.alternatives.some( ( a ) => a.postId === event.detail.popupId )
			) )
			?.forEach( ( exp ) =>
				nab?.view( exp.id )
			);
	} );
	";
	wp_add_inline_script( 'jquery', $script, 'after' );
}//end maybe_add_tracking_script()
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_add_tracking_script', 99 );
