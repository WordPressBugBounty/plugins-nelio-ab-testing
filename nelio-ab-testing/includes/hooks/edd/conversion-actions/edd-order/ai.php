<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/edd-order conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when completed downloads are the goal.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'type'  => array(
					'type'        => 'string',
					'const'       => 'download-selection',
					'description' => 'Selects downloads whose purchases count as conversions.',
				),
				'value' => array(
					'description' => 'Selection of downloads whose purchases count as conversions.',
					'oneOf'       => array(
						array(
							'type'       => 'object',
							'properties' => array(
								'type' => array(
									'type'        => 'string',
									'const'       => 'all-downloads',
									'description' => 'Selects all downloads.',
								),
							),
							'required'   => array( 'type' ),
						),
						array(
							'type'       => 'object',
							'properties' => array(
								'type'  => array(
									'type'        => 'string',
									'const'       => 'some-downloads',
									'description' => 'Selects a subset of downloads.',
								),
								'value' => array(
									'description' => 'Details of the selected downloads.',
									'oneOf'       => array(
										array(
											'type'       => 'object',
											'properties' => array(
												'type'     => array(
													'type' => 'string',
													'const' => 'download-ids',
													'description' => 'Selects downloads by their identifiers.',
												),
												'downloadIds' => array(
													'type' => 'array',
													'description' => 'Identifiers of the downloads whose purchases should count as conversions.',
													'items' => array( 'type' => 'integer' ),
												),
												'mode'     => array(
													'type' => 'string',
													'enum' => array( 'or', 'and' ),
													'description' => 'Whether purchases matching any or all selected downloads count as conversions.',
												),
												'excluded' => array(
													'type' => 'boolean',
													'description' => 'Whether purchases of the selected downloads should be excluded.',
												),
											),
											'required'   => array( 'type', 'downloadIds', 'mode' ),
										),
										array(
											'type'       => 'object',
											'properties' => array(
												'type'  => array(
													'type' => 'string',
													'const' => 'download-taxonomies',
													'description' => 'Selects downloads by product taxonomy terms.',
												),
												'value' => array(
													'type' => 'array',
													'description' => 'Taxonomy-term selections used to select downloads.',
													'items' => array(
														'type' => 'object',
														'properties' => array(
															'type' => array(
																'type' => 'string',
																'const' => 'download-terms',
																'description' => 'Identifies a taxonomy-term download selection.',
															),
															'taxonomy' => array(
																'type' => 'string',
																'description' => 'Taxonomy containing the selected download terms.',
															),
															'termIds' => array(
																'type' => 'array',
																'description' => 'Identifiers of the terms used to select downloads.',
																'items' => array( 'type' => 'integer' ),
															),
															'mode' => array(
																'type' => 'string',
																'enum' => array( 'or', 'and' ),
																'description' => 'Whether purchases matching any or all selected terms count as conversions.',
															),
															'excluded' => array(
																'type' => 'boolean',
																'description' => 'Whether downloads matching the selected terms should be excluded.',
															),
														),
														'required' => array( 'type', 'taxonomy', 'termIds', 'mode' ),
													),
												),
											),
										),
									),
								),
							),
							'required'   => array( 'type', 'value' ),
						),
					),
				),
			),
			'required'             => array( 'type', 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/edd-order_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates Easy Digital Downloads order conversion action attributes.
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
	$selection  = is_array( $attributes['value'] ?? null ) ? $attributes['value'] : array();
	if ( 'some-downloads' === ( $selection['type'] ?? null ) ) {
		$selected = is_array( $selection['value'] ?? null ) ? $selection['value'] : array();
		if ( 'download-ids' === ( $selected['type'] ?? null ) && empty( $selected['downloadIds'] ?? array() ) ) {
			$issues[] = 'At least one download must be selected when tracking specific downloads.';
		}
		if ( 'download-taxonomies' === ( $selected['type'] ?? null ) ) {
			$taxonomies = is_array( $selected['value'] ?? null ) ? $selected['value'] : array();
			if ( empty( $taxonomies ) ) {
				$issues[] = 'At least one download taxonomy must be selected when tracking downloads by taxonomy.';
			} else {
				foreach ( $taxonomies as $taxonomy ) {
					if ( ! is_array( $taxonomy ) || empty( $taxonomy['termIds'] ?? array() ) ) {
						$issues[] = 'Each selected download taxonomy must include at least one term.';
						break;
					}
				}
			}
		}
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/edd-order_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
