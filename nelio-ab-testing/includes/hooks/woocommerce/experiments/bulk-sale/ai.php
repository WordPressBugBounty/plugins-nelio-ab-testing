<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/wc-bulk-sale experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	$term_schema = array(
		'type'                 => 'object',
		'properties'           => array(
			'type'     => array(
				'type'  => 'string',
				'const' => 'product-terms',
			),
			'taxonomy' => array(
				'type'        => 'string',
				'enum'        => array( 'product_cat', 'product_tag' ),
				'description' => 'Selects the product category taxonomy or the product tag taxonomy.',
			),
			'termIds'  => array(
				'type'        => 'array',
				'minItems'    => 1,
				'items'       => array( 'type' => 'integer' ),
				'description' => 'Lists the term identifiers within the selected taxonomy. Use only term identifiers returned by product-term discovery.',
			),
			'mode'     => array(
				'type'        => 'string',
				'const'       => 'or',
				'description' => 'Combines the selected terms with OR logic.',
			),
		),
		'required'             => array( 'type', 'taxonomy', 'termIds', 'mode' ),
		'additionalProperties' => false,
	);

	$selection_schema = array(
		'type'                 => 'object',
		'properties'           => array(
			'type'  => array(
				'type'        => 'string',
				'const'       => 'product-taxonomies',
				'description' => 'Identifies the selected products through product taxonomies.',
			),
			'value' => array(
				'type'        => 'array',
				'minItems'    => 1,
				'maxItems'    => 2,
				'description' => 'Contains at most one product category and one product tag. If both are present, products must match both selections.',
				'items'       => $term_schema,
			),
		),
		'required'             => array( 'type', 'value' ),
		'additionalProperties' => false,
	);

	$product_selection_schema = array(
		'oneOf' => array(
			array(
				'type'                 => 'object',
				'properties'           => array(
					'type' => array(
						'type'        => 'string',
						'const'       => 'all-products',
						'description' => 'Applies the bulk sale to every product.',
					),
				),
				'required'             => array( 'type' ),
				'additionalProperties' => false,
			),
			array(
				'type'                 => 'object',
				'properties'           => array(
					'type'  => array(
						'type'        => 'string',
						'const'       => 'some-products',
						'description' => 'Applies the bulk sale only to the selected products.',
					),
					'value' => $selection_schema,
				),
				'required'             => array( 'type', 'value' ),
				'additionalProperties' => false,
			),
		),
	);

	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to test pricing or sale prices across a group of products, rather than the content or layout of individual product pages. Before configuring a taxonomy-based selection, discover the available product categories or tags and use their returned term identifiers.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'            => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'productSelections' => array(
						'type'        => 'array',
						'minItems'    => 1,
						'maxItems'    => 1,
						'description' => 'Selects the products whose prices are affected by the bulk sale. Use either all products or up to one product category and one product tag, combined with OR mode.',
						'items'       => $product_selection_schema,
					),
				),
				'required'             => array( 'productSelections' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'                      => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'                        => array(
						'type'        => 'string',
						'description' => 'Name of the variant sale.',
					),
					'discount'                    => array(
						'type'        => 'number',
						'description' => 'Discount percentage applied by the variant, from 0 to 100.',
					),
					'overwritesExistingSalePrice' => array(
						'type'        => 'boolean',
						'description' => 'Determines whether the tested discount replaces an existing sale price.',
					),
				),
				'required'             => array( 'name', 'discount', 'overwritesExistingSalePrice' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/wc-bulk-sale_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates bulk-sale test control attributes.
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
add_filter( 'nab_nab/wc-bulk-sale_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates bulk-sale test variant attributes.
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
	$discount = $attributes['discount'] ?? null;
	if ( ! is_numeric( $discount ) || 0 > $discount || 100 < $discount ) {
		$validation['issues'][] = 'The variant discount is not a percentage between 0 and 100.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/wc-bulk-sale_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );
