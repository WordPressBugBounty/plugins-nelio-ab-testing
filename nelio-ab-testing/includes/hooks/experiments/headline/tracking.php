<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function add_tracking_hooks() {

	$exps_with_loaded_alts = array();


	$save_loaded_alternative_for_triggering_page_view_events_later = function ( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {

		add_filter(
			'the_title',
			function ( $title, $post_id ) use ( $alternative, $control, $experiment_id, &$exps_with_loaded_alts ) {
				if ( $post_id === $control['postId'] && ! in_array( $experiment_id, $exps_with_loaded_alts, true ) ) {
					array_push( $exps_with_loaded_alts, $experiment_id );
				}//end if
				return $title;
			},
			10,
			2
		);
	};
	add_action( 'nab_nab/headline_load_alternative', $save_loaded_alternative_for_triggering_page_view_events_later, 10, 3 );

	add_filter( 'nab_nab/headline_get_page_view_tracking_location', fn() => 'footer' );
	add_filter(
		'nab_nab/headline_should_trigger_footer_page_view',
		function ( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {

			if ( is_singular() && nab_get_queried_object_id() === $control['postId'] ) {
				return false;
			}//end if

			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);
}//end add_tracking_hooks()
add_tracking_hooks();
