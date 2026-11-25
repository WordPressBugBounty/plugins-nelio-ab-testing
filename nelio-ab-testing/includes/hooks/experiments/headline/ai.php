<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/headline experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to compare alternative headlines, featured images, or excerpts while keeping the rest of the content unchanged. Before assigning the control content, discover an existing post and use its returned identifier.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'   => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'postType' => array(
						'type'        => 'string',
						'const'       => 'post',
						'description' => 'WordPress content type of the tested post.',
					),
					'postId'   => array(
						'type'        => 'integer',
						'description' => 'Identifier of the post whose headline is tested.',
					),
				),
				'required'             => array( 'postType', 'postId' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'   => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'     => array(
						'type'        => 'string',
						'description' => 'Name of the alternative headline.',
					),
					'excerpt'  => array(
						'type'        => 'string',
						'description' => 'Excerpt used by the alternative.',
					),
					'imageId'  => array(
						'type'        => 'integer',
						'description' => 'Identifier of the alternative featured image.',
					),
					'imageUrl' => array(
						'type'        => 'string',
						'description' => 'URL of the alternative featured image.',
					),
				),
				'required'             => array( 'name', 'excerpt', 'imageId', 'imageUrl' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/headline_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates headline test control attributes.
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
	if ( empty( $attributes['postId'] ) ) {
		$validation['issues'][] = 'The control post ID is missing.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/headline_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates headline test variant attributes.
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
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/headline_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );
