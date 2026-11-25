<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Time_On_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/time-on-page conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when sustained attention is the relevant outcome.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'value' => array(
					'type'        => 'number',
					'description' => 'Number of seconds the visitor must remain on the page.',
				),
			),
			'required'             => array( 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/time-on-page_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates time-on-page conversion action attributes.
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
add_filter( 'nab_nab/time-on-page_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
