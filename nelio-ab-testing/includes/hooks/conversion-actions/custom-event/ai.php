<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/custom-event conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when the desired event cannot be represented reliably by one of the built-in conversion actions.',
		'api'     => array(
			'convert'              => array(
				'signature'   => '() => void',
				'description' => 'Triggers a conversion using the configured goal and variant settings. A custom-event snippet must call this function when the desired event occurs.',
			),
			'utils.onVariantReady' => array(
				'signature'   => '(callback: () => unknown) => void',
				'description' => 'Runs a callback when the tested variant is ready.',
			),
		),
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'snippet' => array(
					'type'        => 'string',
					'description' => 'JavaScript snippet that signals the custom event.',
				),
			),
			'required'             => array( 'snippet' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/custom-event_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates custom-event conversion action attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $action ) {
	$attributes = $action['attributes'];
	$sanitized  = sanitize( $attributes, $action );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );

	$snippet = $attributes['snippet'] ?? '';
	if ( ! is_string( $snippet ) || '' === trim( $snippet ) ) {
		$issues[] = 'The `snippet` attribute is empty.';
	}

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/custom-event_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
