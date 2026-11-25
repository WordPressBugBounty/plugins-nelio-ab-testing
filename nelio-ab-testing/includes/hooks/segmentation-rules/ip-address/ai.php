<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Ip_Address;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/ip-address segmentation rule type.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when an IP address identifies an audience that should receive a different experience.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-equal-to', 'is-not-equal-to' ),
					'description' => 'Condition applied to the visitor IP address.',
				),
				'values'    => array(
					'type'        => 'array',
					'description' => 'IP addresses used for the match.',
				),
			),
			'required'             => array( 'condition', 'values' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/ip-address_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates IP-address segmentation rule attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TSegmentation_Rule     $rule       Segmentation rule.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $rule ) {
	$attributes  = $rule['attributes'];
	$sanitized   = sanitize( $attributes );
	$issues      = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );
	$is_valid_ip = static function ( $value ) {
		$parts = is_string( $value ) ? explode( '.', $value ) : array();
		if ( 4 !== count( $parts ) ) {
			return false;
		}

		foreach ( $parts as $part ) {
			if ( '*' === $part ) {
				continue;
			}

			if ( ! preg_match( '/^\d+$/', $part ) || 255 < (int) $part ) {
				return false;
			}
		}

		return true;
	};
	$values      = is_array( $attributes['values'] ?? null ) ? $attributes['values'] : array();
	if ( empty( $values ) ) {
		$issues[] = 'The IP-address rule must contain at least one IP address.';
	} elseif ( array_filter( $values, fn( $value ) => ! $is_valid_ip( $value ) ) ) {
		$issues[] = 'The IP-address rule contains an invalid IP address.';
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/ip-address_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
