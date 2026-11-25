<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Query_Parameter;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/query-parameter segmentation rule type.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when campaign, referral, or other URL parameters identify the audience.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'name'      => array(
					'type'        => 'string',
					'description' => 'Name of the query parameter.',
				),
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-any-of', 'is-none-of', 'is-equal-to', 'is-not-equal-to', 'contains', 'does-not-contain', 'exists', 'does-not-exist' ),
					'description' => 'Condition applied to the query parameter.',
				),
				'value'     => array(
					'type'        => 'string',
					'description' => 'Value used to evaluate the query parameter.',
				),
			),
			'required'             => array( 'name', 'condition', 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/query-parameter_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates query-parameter segmentation rule attributes.
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
	if ( empty( $attributes['name'] ) ) {
		$issues[] = 'The query-parameter rule must specify a parameter name.';
	}
	if ( ! in_array( $attributes['condition'] ?? '', array( 'exists', 'does-not-exist' ), true ) && '' === ( $attributes['value'] ?? '' ) ) {
		$issues[] = 'The query-parameter rule must specify a value unless it only checks whether the parameter exists.';
	}

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/query-parameter_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
