<?php

namespace Nelio_AB_Testing\Updates\five_one;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

// ===================
// NAB/PRODUCT-SUMMARY
// ===================
// Experiment list.
add_filter(
	'nab_experiment_type_column_in_experiment_list',
	function( $type ) {
		return 'nab/product-summary' === $type ? 'nab/wc-product' : $type;
	}
);

// Dupliating a test.
add_filter(
	'nab_duplicate_experiment',
	function( $experiment ) {
		// Update type.
		if ( 'nab/product-summary' === $experiment->get_type() ) {
			update_post_meta( $experiment->ID, '_nab_experiment_type', 'nab/wc-product' );
		}//end if

		// Update goals.
		$goals = array_map(
			__NAMESPACE__ . '\maybe_use_order_revenue_in_goal',
			$experiment->get_goals()
		);
		$goals = json_decode( wp_json_encode( $goals ), ARRAY_A );
		update_post_meta( $experiment->ID, '_nab_goals', $goals );

		// Return.
		return nab_get_experiment( $experiment->ID );
	}
);

function maybe_use_order_revenue_in_goal( $goal ) {
	if ( ! is_array( $goal['attributes'] ) ) {
		$goal['attributes'] = array();
	}//end if

	if ( ! is_array( $goal['conversionActions'] ) ) {
		$goal['conversionActions'] = array();
	}//end if

	$types = isset( $goal['conversionActions'] )
		? wp_list_pluck( $goal['conversionActions'], 'type' )
		: array();
	$types = array_unique( $types );

	if ( count( $types ) !== 1 ) {
		return $goal;
	}//end if
	if ( 'nab/wc-order' !== $types[0] ) {
		return $goal;
	}//end if

	$goal['attributes']['useOrderRevenue'] = true;
	return $goal;
}//end maybe_use_order_revenue_in_goal()

function add_old_testing_hooks() {
	$namespace = '\Nelio_AB_Testing\Experiment_Library\WooCommerce\Product_Experiment';

	// Basic.
	add_action( 'nab_is_nab/product-summary_woocommerce_experiment', '__return_true' );

	// Attributes.
	add_filter(
		'nab_nab/product-summary_sanitize_control_attributes',
		$namespace . '\sanitize_control_attributes'
	);
	add_filter(
		'nab_nab/product-summary_sanitize_alternative_attributes',
		$namespace . '\sanitize_alternative_attributes'
	);

	// Content.
	add_filter(
		'nab_nab/product-summary_get_tested_element',
		$namespace . '\get_tested_element'
	);
	add_filter(
		'nab_nab/product-summary_backup_control',
		$namespace . '\backup_control',
		10,
		2
	);
	add_filter(
		'nab_nab/product-summary_apply_alternative',
		$namespace . '\apply_alternative',
		10,
		5
	);

	// Load.
	add_action(
		'nab_nab/product-summary_load_alternative',
		$namespace . '\load_woocommerce_alternative',
		10,
		2
	);

	// Preview.
	add_filter(
		'nab_nab/product-summary_preview_link_alternative',
		$namespace . '\get_preview_link',
		10,
		3
	);
	add_action(
		'nab_nab/product-summary_preview_alternative',
		$namespace . '\load_woocommerce_alternative',
		10,
		2
	);

	// Tracking.
	add_filter(
		'nab_nab/product-summary_track_page_views_in_footer',
		'__return_true'
	);
	add_action(
		'nab_nab/product-summary_load_alternative',
		function( $alt, $control, $eid, $aid ) {
			$type = 'nab/wc-product';
			do_action( "nab_{$type}_load_alternative", $alt, $control, $eid, $aid );
		},
		10,
		4
	);
	add_filter(
		'nab_nab/product-summary_should_trigger_footer_page_view',
		function( $result, $alt, $control, $eid, $aid ) {
			$type = 'nab/wc-product';
			return apply_filters( "nab_{$type}_should_trigger_footer_page_view", $result, $alt, $control, $eid, $aid );
		},
		10,
		4
	);
}//end add_old_testing_hooks()
add_old_testing_hooks();
