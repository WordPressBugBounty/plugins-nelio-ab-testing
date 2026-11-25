<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/wc-product experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'fallback',
			'requiresExplicitRequest' => false,
			'useWhen'                 => 'Use it when CSS or JavaScript cannot express the change conveniently, or when you need to test product-specific aspects such as pricing, presentation, gallery images, or other product details. Before assigning the control product, discover an existing product and use its returned identifier.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'              => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'postId'              => array(
						'type'        => 'integer',
						'description' => 'Identifier of the control product.',
					),
					'postType'            => array(
						'type'  => 'string',
						'const' => 'product',
					),
					'disablePriceTesting' => array(
						'type'        => 'boolean',
						'const'       => true,
						'description' => 'Prevents the product price from being modified in the alternative.',
					),
				),
				'required'             => array( 'postId', 'postType' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance' => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'   => array(
						'type'        => 'string',
						'description' => 'Name of the alternative product.',
					),
					'postId' => array(
						'type'        => 'integer',
						'description' => 'Identifier of an existing alternative product. Leave it unset so the plugin creates the alternative product automatically.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/wc-product_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates product test control attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Control attributes.
 * @param int                    $experiment_id Experiment identifier.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_control_attributes( $validation, $attributes, $experiment_id ) {
	$experiment = $experiment_id ? nab_get_experiment( absint( $experiment_id ) ) : null;
	$sanitized  = is_object( $experiment ) && ! is_wp_error( $experiment ) ? sanitize_control_attributes( $attributes, $experiment ) : array();
	$issues     = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	if ( empty( $attributes['postId'] ) ) {
		$validation['issues'][] = 'The control must identify the product being tested.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/wc-product_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 3 );
/**
 * Validates product test variant attributes.
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
add_filter( 'nab_nab/wc-product_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );
