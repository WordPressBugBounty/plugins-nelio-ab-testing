<?php
/**
 * AI metadata for the synced-pattern experiment type.
 *
 * @package Nelio_AB_Testing
 */

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/synced-pattern experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to run an A/B test on a reusable synced pattern that appears across pages. The original must be an existing synced pattern, so identify it before assigning its pattern ID. Variants can be created automatically by duplicating the original, or can use an existing synced pattern when the user explicitly requests that.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'                      => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'patternId'                   => array(
						'type'        => 'integer',
						'description' => 'Identifier of the existing synced pattern used as the original.',
					),
					'testAgainstExistingPatterns' => array(
						'type'        => 'boolean',
						'description' => 'Indicates that the alternative uses an existing published synced pattern.',
					),
				),
				'required'             => array( 'patternId' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'            => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'              => array(
						'type'        => 'string',
						'description' => 'Name of the alternative synced pattern.',
					),
					'patternId'         => array(
						'type'        => 'integer',
						'description' => 'Identifier of an existing synced pattern to use as this variant. Leave it unset so the plugin creates the variant by duplicating the original, unless the user explicitly asks to test an existing synced pattern.',
					),
					'isExistingContent' => array(
						'type'        => 'boolean',
						'const'       => true,
						'description' => 'Indicates that this variant uses an existing synced pattern instead of one created by duplicating the original.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/synced-pattern_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates synced pattern test control attributes.
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
	if ( empty( $attributes['patternId'] ) ) {
		$validation['issues'][] = 'The control synced-pattern ID is missing.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/synced-pattern_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates synced pattern test variant attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Variant attributes.
 * @param int                    $experiment_id Experiment identifier.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_alternative_attributes( $validation, $attributes, $experiment_id ) {
	$sanitized = sanitize_alternative_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	$experiment = $experiment_id ? nab_get_experiment( absint( $experiment_id ) ) : null;
	$control    = is_object( $experiment ) && ! is_wp_error( $experiment ) ? $experiment->get_alternative( 'control' ) : array();
	if ( ! empty( $control['attributes']['testAgainstExistingPatterns'] ) && empty( $attributes['patternId'] ) ) {
		$validation['issues'][] = 'An existing synced-pattern ID is required when the test compares existing patterns.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/synced-pattern_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 3 );
