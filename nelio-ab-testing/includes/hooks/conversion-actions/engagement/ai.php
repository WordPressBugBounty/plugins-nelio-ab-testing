<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/engagement conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when engagement is more appropriate than one specific built-in action.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/engagement_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates engagement conversion action attributes.
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
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/engagement_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
