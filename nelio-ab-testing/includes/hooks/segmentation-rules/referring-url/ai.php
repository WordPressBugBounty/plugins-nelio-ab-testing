<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Referring_Url;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/referring-url segmentation rule type.
 *
 * @return TSegmentation_Rule_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when the visitor source or referring page matters.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-any-of', 'is-none-of', 'is-equal-to', 'is-not-equal-to', 'contains', 'does-not-contain' ),
					'description' => 'Condition applied to the referring URL.',
				),
				'value'     => array(
					'type'        => 'string',
					'description' => 'URL or URL pattern used for the match.',
				),
			),
			'required'             => array( 'condition', 'value' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/referring-url_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates referring-URL segmentation rule attributes.
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
	if ( '' === ( $attributes['value'] ?? '' ) ) {
		$issues[] = 'The referring-URL rule must specify a URL or URL pattern.';
	}

	$value     = is_string( $attributes['value'] ?? null ) ? trim( $attributes['value'] ) : '';
	$condition = $attributes['condition'] ?? '';
	if ( '' !== $value ) {
		if ( in_array( $condition, array( 'is-equal-to', 'is-not-equal-to' ), true ) ) {
			$error = nab_ai_is_url_invalid( $value );
			if ( $error ) {
				$issues[] = $error;
			}
		} elseif ( in_array( $condition, array( 'contains', 'does-not-contain' ), true ) ) {
			$error = nab_ai_is_url_fragment_invalid( $value );
			if ( $error ) {
				$issues[] = $error;
			}
		} elseif ( in_array( $condition, array( 'is-any-of', 'is-none-of' ), true ) ) {
			$values = array_values( array_filter( array_map( 'trim', explode( "\n", $value ) ) ) );
			foreach ( $values as $item ) {
				$error = nab_ai_is_url_invalid( $item );
				if ( $error ) {
					$issues[] = $error;
					break;
				}
			}
		}
	}

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/referring-url_ai_validate_segmentation_rule_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
