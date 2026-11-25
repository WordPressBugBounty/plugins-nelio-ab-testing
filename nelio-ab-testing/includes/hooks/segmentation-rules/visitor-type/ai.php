<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Visitor_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/visitor-type segmentation rule type.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when prior visits may influence the experiment outcome.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-new-visitor', 'is-returning-visitor' ),
					'description' => 'Condition that determines whether the visitor is new or returning.',
				),
			),
			'required'             => array( 'condition' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/visitor-type_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates visitor-type segmentation rule attributes.
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
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/visitor-type_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
