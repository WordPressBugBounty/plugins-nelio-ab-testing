<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes                  $control    Control.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return TWC_Product_Control_Attributes
 */
function sanitize_control_attributes( $control, $experiment ) {
	/** @var TWC_Product_Control_Attributes */
	$defaults = array(
		'postId'   => 0,
		'postType' => 'product',
	);

	/** @var TWC_Product_Control_Attributes */
	$control = wp_parse_args( $control, $defaults );

	$scope = $experiment->get_scope();
	$scope = $scope[0]['attributes']['type'] ?? '';
	if ( 'tested-post' === $scope ) {
		$control['disablePriceTesting'] = true;
	}

	return $control;
}
add_filter( 'nab_nab/wc-product_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes', 10, 2 );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TWC_Product_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TWC_Product_Alternative_Attributes */
	$defaults = array(
		'name'   => '',
		'postId' => 0,
	);

	/** @var TWC_Product_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/wc-product_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
