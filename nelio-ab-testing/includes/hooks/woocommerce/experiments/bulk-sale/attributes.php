<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_control_attributes( $control ) {
	$defaults = array(
		'productSelections' => array(
			array( 'type' => 'all-products' ),
		),
	);

	if ( empty( $control ) ) {
		return $defaults;
	}//end if

	if ( ! is_array( $control ) ) {
		return $defaults;
	}//end if

	if ( empty( $control['productSelections'] ) ) {
		return $defaults;
	}//end if

	if ( count( $control['productSelections'] ) !== 1 ) {
		return $defaults;
	}//end if

	return $control;
}//end sanitize_control_attributes()
add_filter( 'nab_nab/wc-bulk-sale_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'                        => '',
		'discount'                    => 20,
		'overwritesExistingSalePrice' => true,
	);
	return wp_parse_args( $alternative, $defaults );
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/wc-bulk-sale_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
