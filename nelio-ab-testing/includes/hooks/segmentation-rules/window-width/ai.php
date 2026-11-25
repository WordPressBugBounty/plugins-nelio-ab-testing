<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Window_Width;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/window-width segmentation rule type.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when a responsive layout or viewport size may affect the experience.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-greater-than', 'is-less-than', 'between' ),
					'description' => 'Comparison applied to the browser window width.',
				),
				'value'     => array(
					'type'        => 'number',
					'description' => 'Width value used for greater-than or less-than comparisons.',
				),
				'interval'  => array(
					'type'        => 'object',
					'description' => 'Minimum and maximum width used for a between comparison.',
				),
			),
			'required'             => array( 'condition', 'interval' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/window-width_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates window-width segmentation rule attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TSegmentation_Rule     $rule       Segmentation rule.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $rule ) {
	$attributes = $rule['attributes'];
	$sanitized  = sanitize( $attributes );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );
	$condition  = $attributes['condition'] ?? '';
	if ( 'between' !== $condition && ! isset( $attributes['value'] ) ) {
		$issues[] = 'The window-width rule must specify a width value.';
	}
	if ( 'between' === $condition ) {
		$interval = is_array( $attributes['interval'] ?? null ) ? $attributes['interval'] : array();
		if ( ! isset( $interval['min'] ) ) {
			$issues[] = 'The window-width rule must specify a minimum width.';
		}
		if ( ! isset( $interval['max'] ) ) {
			$issues[] = 'The window-width rule must specify a maximum width.';
		}
		if ( isset( $interval['min'], $interval['max'] ) && $interval['min'] >= $interval['max'] ) {
			$issues[] = 'The maximum width in a window-width rule must be greater than its minimum width.';
		}
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/window-width_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
