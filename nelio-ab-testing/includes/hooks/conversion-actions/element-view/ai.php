<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Element_View;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/element-view conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when exposure to a page element is the meaningful interaction.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'mode'  => array(
					'type'        => 'string',
					'enum'        => array( 'css', 'id', 'class' ),
					'description' => 'Specifies how the element is identified.',
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'Identifies the element whose visibility must be detected.',
				),
				'delay' => array(
					'type'        => 'number',
					'description' => 'Minimum number of milliseconds after page load before the element view can be counted.',
				),
			),
			'required'             => array( 'mode', 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/element-view_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates an element-view conversion action.
 *
 * @param TAttributes_Validation $validation    Validation result.
 * @param TConversion_Action     $action       Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $action ) {
	$attributes = $action['attributes'];
	$sanitized  = sanitize( $attributes );
	$mode       = is_string( $attributes['mode'] ?? null ) ? $attributes['mode'] : '';
	$value      = is_string( $attributes['value'] ?? null ) ? trim( $attributes['value'] ) : '';
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );

	if ( ! in_array( $mode, array( 'css', 'id', 'class' ), true ) ) {
		$issues[] = 'The `mode` attribute does not specify a CSS selector, an HTML id, or a class name.';
	}
	if ( '' === $value ) {
		$issues[] = 'The `value` attribute contains an empty element target.';
	}

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/element-view_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
add_filter( 'nab_nab/element-view_ai_validate_page_view_trigger_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
