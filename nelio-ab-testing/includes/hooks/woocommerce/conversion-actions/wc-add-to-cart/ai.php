<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Add_To_Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/wc-add-to-cart conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'instructions' => 'Use it when cart additions are a meaningful step toward purchase.',
		'schema'       => array(
			'type'                 => 'object',
			'properties'           => array(
				'anyProduct' => array(
					'type'        => 'boolean',
					'description' => 'Whether any product qualifies for the conversion.',
				),
				'productId'  => array(
					'type'        => 'integer',
					'description' => 'Identifier of the product that qualifies for the conversion.',
				),
			),
			'required'             => array( 'anyProduct', 'productId' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/wc-add-to-cart_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates WooCommerce add-to-cart conversion action attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $action ) {
	$attributes = $action['attributes'];
	$sanitized  = sanitize( $attributes );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );
	if ( ! $attributes['anyProduct'] && empty( $attributes['productId'] ) ) {
		$issues[] = 'Either a product must be selected or tracking for any product must be enabled.';
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/wc-add-to-cart_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
