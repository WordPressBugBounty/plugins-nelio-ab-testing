<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $control Control attributes.
 *
 * @return TWC_Bulk_Sale_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var TWC_Bulk_Sale_Control_Attributes */
	$defaults = array(
		'productSelections' => array(
			array( 'type' => 'all-products' ),
		),
	);

	if ( empty( $control ) ) {
		return $defaults;
	}

	if ( empty( $control['productSelections'] ) ) {
		return $defaults;
	}

	if ( ! is_array( $control['productSelections'] ) ) {
		return $defaults;
	}

	if ( count( $control['productSelections'] ) !== 1 ) {
		return $defaults;
	}

	/** @var TWC_Bulk_Sale_Control_Attributes */
	return $control;
}
add_filter( 'nab_nab/wc-bulk-sale_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TWC_Bulk_Sale_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'                        => Z::string()->default( '' )->trim(),
				'chance'                      => Z::number()->optional(),
				'discount'                    => Z::number()->default( 20 ),
				'overwritesExistingSalePrice' => Z::boolean()->default( true ),
			)
		)->catch(
			fn() => array(
				'name'                        => '',
				'discount'                    => 20,
				'overwritesExistingSalePrice' => true,
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TWC_Bulk_Sale_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/wc-bulk-sale_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
