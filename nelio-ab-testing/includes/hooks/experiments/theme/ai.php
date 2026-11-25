<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/theme experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to compare site-wide presentation or theme changes rather than a single page or element.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance' => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
				),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'  => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'    => array(
						'type'        => 'string',
						'description' => 'Name of the alternative theme.',
					),
					'themeId' => array(
						'type'        => 'string',
						'description' => 'Identifier of the alternative theme.',
					),
				),
				'required'             => array( 'name', 'themeId' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/theme_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates theme test control attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Control attributes.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_control_attributes( $validation, $attributes ) {
	$sanitized = sanitize_control_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/theme_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates theme test variant attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Variant attributes.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_alternative_attributes( $validation, $attributes ) {
	$sanitized = sanitize_alternative_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	if ( empty( $attributes['themeId'] ) ) {
		$validation['issues'][] = 'The variant theme ID is missing.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/theme_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );
