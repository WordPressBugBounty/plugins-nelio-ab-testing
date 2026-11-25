<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\User_Login;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/user-login segmentation rule type.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when authenticated and anonymous visitors should receive different experiences.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-logged-in', 'is-not-logged-in' ),
					'description' => 'Condition that determines whether the visitor is authenticated.',
				),
			),
			'required'             => array( 'condition' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/user-login_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates user-login segmentation rule attributes.
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
add_filter( 'nab_nab/user-login_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
