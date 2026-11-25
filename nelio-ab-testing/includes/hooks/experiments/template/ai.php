<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/template experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to compare the layout or rendering templates used for pages, products, 404 pages, or another supported template context. Before configuring the control or a variant, discover an existing template and use its returned builder, context, post type, template identifier, and name instead of inventing these values.',
		),
		'schema'    => array(
			'original'    => array(
				'oneOf' => array(
					array(
						'type'                 => 'object',
						'properties'           => array(
							'chance'     => array(
								'type'        => 'number',
								'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
							),
							'postType'   => array(
								'type'        => 'string',
								'description' => 'WordPress content type associated with the template.',
							),
							'templateId' => array(
								'type'        => 'string',
								'description' => 'Identifier of the existing template.',
							),
							'name'       => array(
								'type'        => 'string',
								'description' => 'Name of the template.',
							),
						),
						'required'             => array( 'postType', 'name' ),
						'additionalProperties' => false,
					),
					array(
						'type'                 => 'object',
						'properties'           => array(
							'chance'     => array(
								'type'        => 'number',
								'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
							),
							'builder'    => array(
								'type'        => 'string',
								'description' => 'Identifier of the page-builder system used by the template.',
							),
							'context'    => array(
								'type'        => 'string',
								'description' => 'Template context where the template is applied, such as a single page, single product, or 404 page.',
							),
							'templateId' => array(
								'type'        => 'string',
								'description' => 'Identifier of the existing page-builder template.',
							),
							'name'       => array(
								'type'        => 'string',
								'description' => 'Name of the template.',
							),
						),
						'required'             => array( 'builder', 'context', 'name' ),
						'additionalProperties' => false,
					),
				),
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'     => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'templateId' => array(
						'type'        => 'string',
						'description' => 'Identifier of the alternative template.',
					),
					'name'       => array(
						'type'        => 'string',
						'description' => 'Name of the alternative template.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/template_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates template test control attributes.
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
	if ( empty( $attributes['templateId'] ) ) {
		$validation['issues'][] = 'The control template ID is missing.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/template_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates template test variant attributes.
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
	if ( empty( $attributes['templateId'] ) ) {
		$validation['issues'][] = 'The variant template ID is missing.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/template_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );
