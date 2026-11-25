<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Click_External_Link;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/click-external-link conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when success is defined by sending visitors to a destination outside the site.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'mode'  => array(
					'type'        => 'string',
					'enum'        => array( 'exact', 'partial', 'start', 'end', 'regex' ),
					'description' => 'Specifies how the external URL is matched.',
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'URL or URL pattern that must be clicked.',
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
add_filter( 'nab_nab/click-external-link_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates click-external-link conversion action attributes.
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
		$issues[] = 'An external URL or URL pattern is required.';
	}

	$mode = $attributes['mode'] ?? '';
	if ( 'regex' === $mode ) {
		// NOTICE. This is a JavaScript regex expression so it won’t be validated in PHP.
		$attributes['mode'] = 'regex'; // @codeCoverageIgnore
	} else {
		$error = nab_ai_is_url_fragment_invalid( $value );
		if ( is_string( $error ) ) {
			$issues[] = $error;
		}
	}

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/click-external-link_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
