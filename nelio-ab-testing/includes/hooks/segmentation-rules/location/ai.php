<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Location;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/location segmentation rule type.
 *
 * @param TSegmentation_Rule_Type_AI|null    $ai         Previous AI definition.
 * @param TSegmentation_Rule_Type_Definition $definition Segmentation rule type definition.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition( $ai, $definition ) {
	$values = $definition['options'] ?? array();
	return array(
		'useWhen' => 'Use it when regional context, market, or local audience differences may affect the experiment.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-equal-to', 'is-not-equal-to' ),
					'description' => 'Condition applied to the visitor location.',
				),
				'location'  => array(
					'type'        => 'array',
					'description' => 'Locations used for the match.',
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'value' => array(
								'type'        => 'string',
								'enum'        => array_keys( $values ),
								'description' => 'Location value used for the match.',
							),
							'label' => array(
								'type'        => 'string',
								'description' => 'Human-readable location label.',
							),
						),
						'required'             => array( 'value', 'label' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'condition', 'location' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/location_ai_definition', __NAMESPACE__ . '\register_ai_definition', 10, 2 );

/**
 * Validates location segmentation rule attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TSegmentation_Rule     $rule       Segmentation rule.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $rule ) {
	$attributes = $rule['attributes'];
	$sanitized  = sanitize( $attributes );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );
	if ( empty( $attributes['location'] ) ) {
		$issues[] = 'The location rule must contain at least one location.';
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/location_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
