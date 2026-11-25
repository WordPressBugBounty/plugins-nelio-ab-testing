<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/wc-order conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'instructions' => 'Use it when completed purchases, rather than intermediate shopping actions, are the goal.',
		'schema'       => array(
			'type'                 => 'object',
			'properties'           => array(
				'type'  => array(
					'type'        => 'string',
					'const'       => 'product-selection',
					'description' => 'Selects products whose purchases count as conversions.',
				),
				'value' => array(
					'oneOf' => array(
						array(
							'type'       => 'object',
							'properties' => array(
								'type' => array(
									'type'        => 'string',
									'const'       => 'all-products',
									'description' => 'Selects all products.',
								),
							),
							'required'   => array( 'type' ),
						),
						array(
							'type'       => 'object',
							'properties' => array(
								'type'  => array(
									'type'  => 'string',
									'const' => 'some-products',
								),
								'value' => array(
									'oneOf' => array(
										array(
											'type'       => 'object',
											'properties' => array(
												'type'     => array(
													'type' => 'string',
													'const' => 'product-ids',
												),
												'productIds' => array(
													'type' => 'array',
													'items' => array( 'type' => 'integer' ),
												),
												'mode'     => array(
													'type' => 'string',
													'enum' => array( 'or', 'and' ),
												),
												'excluded' => array( 'type' => 'boolean' ),
											),
											'required'   => array( 'type', 'productIds', 'mode' ),
										),
										array(
											'type'       => 'object',
											'properties' => array(
												'type'  => array(
													'type' => 'string',
													'const' => 'product-taxonomies',
												),
												'value' => array(
													'type' => 'array',
													'items' => array(
														'type' => 'object',
														'properties' => array(
															'type' => array(
																'type' => 'string',
																'const' => 'product-terms',
															),
															'taxonomy' => array( 'type' => 'string' ),
															'termIds' => array(
																'type' => 'array',
																'items' => array( 'type' => 'integer' ),
															),
															'mode' => array(
																'type' => 'string',
																'enum' => array( 'or', 'and' ),
															),
															'excluded' => array( 'type' => 'boolean' ),
														),
														'required' => array( 'type', 'taxonomy', 'termIds', 'mode' ),
													),
												),
											),
										),
									),
								),
							),
							'required'   => array( 'type', 'value' ),
						),
					),
				),
			),
			'required'             => array( 'type', 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/wc-order_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates WooCommerce order conversion action attributes.
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
	$selection  = is_array( $attributes['value'] ?? null ) ? $attributes['value'] : array();
	$selected   = is_array( $selection['value'] ?? null ) ? $selection['value'] : array();
	if ( 'some-products' === ( $selection['type'] ?? null ) && empty( $selected['productIds'] ?? array() ) ) {
		$issues[] = 'At least one product must be selected when tracking specific products instead of all products.';
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/wc-order_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
