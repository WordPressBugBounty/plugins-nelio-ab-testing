<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Callback to add tracking hooks.
 *
 * @return void
 */
function add_tracking_hooks() {

	$exps_with_loaded_alts = array();


	$save_loaded_alternative_for_triggering_page_view_events_later = function ( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
		/** @var THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative   */
		/** @var THeadline_Control_Attributes                                  $control       */
		/** @var int                                                           $experiment_id */

		add_filter(
			'the_title',
			function ( $title, $post_id ) use ( $control, $experiment_id, &$exps_with_loaded_alts ) {
				if ( $post_id === $control['postId'] && ! in_array( $experiment_id, $exps_with_loaded_alts, true ) ) {
					array_push( $exps_with_loaded_alts, $experiment_id );
				}
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
			/** @var bool                                                          $result        */
			/** @var THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative   */
			/** @var THeadline_Control_Attributes                                  $control       */
			/** @var int                                                           $experiment_id */

			if ( is_singular() && nab_get_queried_object_id() === $control['postId'] ) {
				return false;
			}

			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);
}
add_tracking_hooks();
