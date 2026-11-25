<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/surecart-order conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'instructions' => 'Use it when completed purchases are the goal.',
		'schema'       => array(
			'type'                 => 'object',
			'properties'           => array(
				'type'  => array(
					'type'        => 'string',
					'const'       => 'surecart-selection',
					'description' => 'Selects SureCart products whose purchases count as conversions.',
				),
				'value' => array(
					'oneOf' => array(
						array(
							'type'       => 'object',
							'properties' => array(
								'type' => array(
									'type'        => 'string',
									'const'       => 'all-surecart-products',
									'description' => 'Selects all SureCart products.',
								),
							),
							'required'   => array( 'type' ),
						),
						array(
							'type'       => 'object',
							'properties' => array(
								'type'  => array(
									'type'  => 'string',
									'const' => 'some-surecart-products',
								),
								'value' => array(
									'type'       => 'object',
									'properties' => array(
										'type'       => array(
											'type'  => 'string',
											'const' => 'surecart-ids',
										),
										'productIds' => array(
											'type'  => 'array',
											'items' => array( 'type' => 'string' ),
										),
										'mode'       => array(
											'type' => 'string',
											'enum' => array( 'or', 'and' ),
										),
										'excluded'   => array( 'type' => 'boolean' ),
									),
									'required'   => array( 'type', 'productIds', 'mode' ),
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
add_filter( 'nab_nab/surecart-order_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates SureCart order conversion action attributes.
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
	if ( 'some-surecart-products' === ( $selection['type'] ?? null ) ) {
		$selected = is_array( $selection['value'] ?? null ) ? $selection['value'] : array();
		if ( empty( $selected['productIds'] ?? array() ) ) {
			$issues[] = 'At least one SureCart product must be selected when tracking specific products.';
		}
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/surecart-order_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
