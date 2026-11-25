<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/url experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to compare existing URLs or when each variant must use a distinct URL for full-page content, layout, or template changes.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'        => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'url'           => array(
						'type'        => 'string',
						'description' => 'URL of the control variant.',
					),
					'useControlUrl' => array(
						'type'        => 'boolean',
						'description' => 'Replaces the alternative URL with the control URL after the alternative loads.',
					),
				),
				'required'             => array( 'url' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance' => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'url'    => array(
						'type'        => 'string',
						'description' => 'URL of the alternative variant.',
					),
				),
				'required'             => array( 'url' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/url_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates URL test control attributes.
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
	$validation = ai_validate_url( $validation, $attributes['url'] ?? '' );
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/url_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates URL test variant attributes.
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
	$validation = ai_validate_url( $validation, $attributes['url'] ?? '' );
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/url_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );

/**
 * Validates a URL according to the URL test's front-end constraints.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param mixed                  $url        URL to validate.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_url( $validation, $url ) {
	if ( ! is_string( $url ) || '' === trim( $url ) || false === wp_http_validate_url( $url ) ) {
		$validation['issues'][] = 'The URL is empty or is not a valid local URL.';
		return $validation;
	}
	$home_url = untrailingslashit( nab_home_url() );
	if ( 0 !== strpos( $url, $home_url ) || ( $url !== $home_url && 0 !== strpos( $url, $home_url . '/' ) ) ) {
		$validation['issues'][] = 'The URL does not start with the WordPress home URL.';
	} elseif ( false !== strpos( $url, '?' ) ) {
		$validation['issues'][] = 'The tested URL contains query arguments, which are not supported.';
	}
	return $validation;
}
