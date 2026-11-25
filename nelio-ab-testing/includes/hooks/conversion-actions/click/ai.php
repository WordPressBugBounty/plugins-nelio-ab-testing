<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Click;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/click conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when the meaningful action is activating a button, link, or other specific element.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'mode'  => array(
					'type'        => 'string',
					'enum'        => array( 'css', 'id', 'class' ),
					'description' => 'Specifies how the clicked element is identified.',
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'Identifies the element that must be clicked.',
				),
				'delay' => array(
					'type'        => 'number',
					'description' => 'Minimum number of milliseconds after page load before a click can be counted.',
				),
			),
			'required'             => array( 'mode', 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/click_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates click conversion action attributes.
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

	$value = is_string( $attributes['value'] ?? null ) ? $attributes['value'] : '';
	if ( '' === $value ) {
		$issues[] = 'A non-empty element target is required.';
	}

	if ( 'id' === $attributes['mode'] && ! preg_match( '/^[a-zA-Z0-9_-]+$/', $value ) ) {
		$issues[] = 'The element ID contains characters other than letters, numbers, hyphens, or underscores.';
	}

	if ( 'class' === $attributes['mode'] && ! preg_match( '/^[a-zA-Z0-9_-]+$/', $value ) ) {
		$issues[] = 'The element class name contains characters other than letters, numbers, hyphens, or underscores.';
	}

	// NOTE: CSS selector syntax cannot be validated reliably on the server because
	// querySelector is browser-dependent and the rendered DOM is unavailable here.
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/click_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
