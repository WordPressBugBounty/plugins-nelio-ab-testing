<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function add_tracking_hooks() {

	$exps_with_loaded_alts = array();

	$save_loaded_alternative_for_triggering_page_view_events_later = function ( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
		add_action(
			'nab_woocommerce_alternative_loaded',
			function ( $eid ) use ( $experiment_id, &$exps_with_loaded_alts ) {
				if ( $eid === $experiment_id ) {
					$exps_with_loaded_alts[ $eid ] = true;
				}//end if
			}
		);
	};
	add_action( 'nab_nab/wc-product_load_alternative', $save_loaded_alternative_for_triggering_page_view_events_later, 1, 3 );

	add_filter( 'nab_nab/wc-product_get_page_view_tracking_location', fn() => 'footer' );
	add_filter(
		'nab_nab/wc-product_should_trigger_footer_page_view',
		function ( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			return in_array( $experiment_id, array_keys( $exps_with_loaded_alts ), true );
		},
		10,
		4
	);
}//end add_tracking_hooks()
add_tracking_hooks();
