<?php
/**
 * Sanitizes and validates experiment segments.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod;

/**
 * Manages experiment segments.
 */
class Nelio_AB_Testing_Segment_Manager {

	/**
	 * Sanitizes experiment segments and their segmentation rules.
	 *
	 * @param list<TSegment> $segments Segments.
	 *
	 * @return list<TSegment> Sanitized segments.
	 */
	public static function sanitize( $segments ) {
		$schema   = self::schema();
		$segments = array_map(
			fn( $segment ) => $schema->safe_parse( $segment ),
			$segments
		);
		$segments = array_map(
			fn( $parsed ) => $parsed['success'] ? $parsed['data'] : null,
			$segments
		);

		/** @var list<TSegment> $segments */
		$segments = array_values( array_filter( $segments, static fn( $s ) => is_array( $s ) ) );
		foreach ( $segments as &$segment ) {
			foreach ( $segment['segmentationRules'] as &$rule ) {
				$rule['attributes'] = self::sanitize_rule_attributes( $rule['attributes'], $rule );
			}
		}

		/** @var list<TSegment> $segments */
		return $segments;
	}

	/**
	 * Normalizes experiment segments before saving them.
	 *
	 * @param list<TSegment> $segments Segments.
	 *
	 * @return list<TSegment> Normalized segments.
	 */
	public static function normalize_pre_save( $segments ) {
		foreach ( $segments as &$segment ) {
			foreach ( $segment['segmentationRules'] as &$rule ) {
				$rule['attributes'] = self::normalize_attributes_pre_save( $rule['attributes'], $rule );
			}
		}

		return $segments;
	}

	/**
	 * Validates experiment segments and their segmentation rules.
	 *
	 * @param list<TSegment> $segments Segments.
	 *
	 * @return true|WP_Error True when all segments are valid; otherwise, an error.
	 */
	public static function ai_validate( $segments ) {
		foreach ( $segments as $segment ) {
			$validation = self::ai_validate_segment( $segment );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		return true;
	}

	/**
	 * Validates one experiment segment and its segmentation rules.
	 *
	 * @param mixed $segment Segment.
	 *
	 * @return true|WP_Error True when the segment is valid; otherwise, an error.
	 */
	public static function ai_validate_segment( $segment ) {
		$parsed_segment = self::schema()->safe_parse( $segment );
		if ( ! $parsed_segment['success'] ) {
			return new WP_Error(
				'invalid-segment',
				_x( 'The segment does not have the required structure.', 'text', 'nelio-ab-testing' )
			);
		}

		/** @var TSegment $segment */
		$segment = $parsed_segment['data'];
		if ( empty( $segment['segmentationRules'] ) ) {
			return new WP_Error( 'invalid-segment', 'Every test segment must contain at least one segmentation rule.' );
		}

		foreach ( $segment['segmentationRules'] as $rule ) {
			$rule_type = $rule['type'];
			if ( ! nelioab()->get_segmentation_rule_type( $rule_type ) ) {
				return new WP_Error(
					'invalid-segmentation-rule',
					sprintf(
						/* translators: %s: Segmentation rule type. */
						_x( 'The segmentation rule type `%s` is not registered on this site.', 'text', 'nelio-ab-testing' ),
						$rule_type
					)
				);
			}

			$validation = self::ai_validate_rule_attributes( $rule );
			if ( 'valid' !== $validation['status'] ) {
				$issues = implode( ' ', $validation['issues'] );
				if ( '' === $issues ) {
					$issues = _x( 'The segmentation rule attributes could not be validated.', 'text', 'nelio-ab-testing' );
				}
				return new WP_Error(
					'invalid-segmentation-rule',
					sprintf(
						// translators: %1$s: Segmentation rule type, %2$s: Validation issues.
						_x( 'The segmentation rule type `%1$s` has invalid attributes: %2$s', 'text', 'nelio-ab-testing' ),
						$rule_type,
						$issues
					)
				);
			}
		}

		return true;
	}

	/**
	 * Sanitizes segmentation-rule attributes.
	 *
	 * @param TAttributes        $attributes Rule attributes.
	 * @param TSegmentation_Rule $rule       Segmentation rule.
	 *
	 * @return TAttributes Sanitized attributes.
	 */
	private static function sanitize_rule_attributes( $attributes, $rule ) {
		$rule_type = $rule['type'];
		/**
		 * Filters a segmentation rule's attributes.
		 *
		 * @param TAttributes        $attributes Rule attributes.
		 * @param TSegmentation_Rule $rule       Segmentation rule.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( "nab_{$rule_type}_sanitize_segmentation_rule_attributes", $attributes, $rule );

		/** @var TAttributes $result */
		return $result;
	}

	/**
	 * Validates segmentation-rule attributes.
	 *
	 * @param TSegmentation_Rule $rule Segmentation rule.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	private static function ai_validate_rule_attributes( $rule ) {
		$rule_type = $rule['type'];
		$result    = array(
			'status' => 'unsupported',
			'issues' => array(),
		);

		/**
		 * Filters the AI validation result for segmentation-rule attributes.
		 *
		 * @param TAttributes_Validation $validation Previous validation result.
		 * @param TSegmentation_Rule     $rule       Segmentation rule.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( "nab_{$rule_type}_ai_validate_segmentation_rule_attributes", $result, $rule );
	}

	/**
	 * Normalizes segmentation-rule attributes before saving.
	 *
	 * @param TAttributes        $attributes Segmentation-rule attributes.
	 * @param TSegmentation_Rule $rule       Segmentation rule.
	 *
	 * @return TAttributes Normalized attributes.
	 */
	public static function normalize_attributes_pre_save( $attributes, $rule ) {
		$rule_type = $rule['type'];
		/**
		 * Filters a segmentation rule type's attributes before saving.
		 *
		 * @param TAttributes        $attributes Segmentation-rule attributes.
		 * @param TSegmentation_Rule $rule       Segmentation rule.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( "nab_{$rule_type}_normalize_segmentation_rule_attributes_pre_save", $attributes, $rule );

		/** @var TAttributes $result */
		return $result;
	}

	/**
	 * Returns the schema for an experiment segment.
	 *
	 * @return Nelio_AB_Testing\Zod\ObjectSchema Segment schema.
	 */
	private static function schema() {
		return Zod::object(
			array(
				'id'                => Zod::string()->trim()->min( 1 ),
				'attributes'        => Zod::object(
					array( 'name' => Zod::string()->trim() )
				)->loose(),
				'segmentationRules' => Zod::array(
					Zod::object(
						array(
							'id'         => Zod::string(),
							'type'       => Zod::string()->trim()->min( 1 ),
							'attributes' => Zod::object( array() )->loose(),
						)
					)
				),
			)
		);
	}
}
