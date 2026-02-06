<?php
namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Adds tracking hooks.
 *
 * @return void
 */
function add_tracking_hooks() {

	/** @var list<int> */
	$exps_with_loaded_alts = array();

	add_action(
		'nab_nab/widget_load_alternative',
		function ( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			/** @var TWidget_Alternative_Attributes|TWidget_Control_Attributes $alternative   */
			/** @var TWidget_Control_Attributes                                $control       */
			/** @var int                                                       $experiment_id */

			add_action(
				'dynamic_sidebar_after',
				function () use ( $experiment_id, &$exps_with_loaded_alts ) {
					array_push( $exps_with_loaded_alts, $experiment_id );
				}
			);
		},
		10,
		3
	);

	add_filter( 'nab_nab/widget_get_page_view_tracking_location', fn() => 'footer' );
	add_filter(
		'nab_nab/widget_should_trigger_footer_page_view',
		function ( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			/** @var bool                                                      $result        */
			/** @var TWidget_Alternative_Attributes|TWidget_Control_Attributes $alternative   */
			/** @var TWidget_Control_Attributes                                $control       */
			/** @var int                                                       $experiment_id */

			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);
}
add_tracking_hooks();
