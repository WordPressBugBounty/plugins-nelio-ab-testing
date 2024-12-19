<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_control_attributes( $control, $experiment ) {
	$defaults = array(
		'postId'   => 0,
		'postType' => 'product',
	);

	$control = wp_parse_args( $control, $defaults );
	if ( 'tested-post' === nab_array_get( $experiment->get_scope(), '0.attributes.type' ) ) {
		$control['disablePriceTesting'] = true;
	}//end if

	return $control;
}//end sanitize_control_attributes()
add_filter( 'nab_nab/wc-product_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes', 10, 2 );

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'   => '',
		'postId' => 0,
	);
	return wp_parse_args( $alternative, $defaults );
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/wc-product_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
