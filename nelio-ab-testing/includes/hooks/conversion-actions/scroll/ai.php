<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Scroll;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/scroll conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when reading or reaching deeper page content is meaningful evidence of interest.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'value' => array(
					'type'        => 'number',
					'description' => 'Percentage of the page that must be scrolled.',
				),
			),
			'required'             => array( 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/scroll_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates scroll conversion action attributes.
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
add_filter( 'nab_nab/scroll_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
