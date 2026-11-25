<?php
/**
 * Sanitizes and normalizes experiment variants.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages experiment variants.
 */
class Nelio_AB_Testing_Variant_Manager {

	/**
	 * Sanitizes the attributes of experiment variants.
	 *
	 * @param list<TAlternative>          $alternatives Variants.
	 * @param Nelio_AB_Testing_Experiment $experiment   Experiment.
	 *
	 * @return list<TAlternative> Sanitized variants.
	 */
	public static function sanitize( $alternatives, $experiment ) {
		if ( empty( $alternatives ) ) {
			$alternatives = array(
				array(
					'id'         => 'control',
					'attributes' => array(),
				),
			);
		}

		$experiment_type = $experiment->get_type();
		$control         = $alternatives[0];

		/**
		 * Sanitizes control attributes.
		 *
		 * @param TAttributes                 $attributes  Current attributes.
		 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
		 *
		 * @since 5.4.0
		 */
		$control['attributes'] = apply_filters( "nab_{$experiment_type}_sanitize_control_attributes", $control['attributes'], $experiment );

		$alternatives = array_slice( $alternatives, 1 );
		$alternatives = array_map(
			function ( $alternative ) use ( $experiment_type, $control, $experiment ) {
				/**
				 * Sanitizes alternative attributes.
				 *
				 * @param TAttributes                 $attributes  Current attributes.
				 * @param TControl_Attributes         $control     Control attributes.
				 * @param Nelio_AB_Testing_Experiment $experiment  Experiment.
				 *
				 * @since 5.4.0
				 */
				$alternative['attributes'] = apply_filters( "nab_{$experiment_type}_sanitize_alternative_attributes", $alternative['attributes'], $control['attributes'], $experiment );
				return $alternative;
			},
			$alternatives
		);

		return array_merge( array( $control ), $alternatives );
	}

	/**
	 * Normalizes experiment variants before saving them.
	 *
	 * @param list<TAlternative>          $alternatives Variants.
	 * @param Nelio_AB_Testing_Experiment $experiment   Experiment.
	 *
	 * @return list<TAlternative> Normalized variants.
	 */
	public static function normalize_pre_save( $alternatives, $experiment ) {
		$experiment_type = $experiment->get_type();
		$control         = $alternatives[0] ?? null;
		if ( empty( $control ) ) {
			return $alternatives;
		}

		return array_map(
			function ( $alternative ) use ( $experiment_type, $control, $experiment ) {
				/**
				 * Normalizes alternative attributes on save.
				 *
				 * @param TAttributes                 $attributes     Current attributes.
				 * @param TControl_Attributes         $control        Control attributes.
				 * @param Nelio_AB_Testing_Experiment $experiment     Experiment.
				 * @param string                      $alternative_id Alternative ID.
				 *
				 * @since 9.0.0
				 */
				$alternative['attributes'] = apply_filters( "nab_{$experiment_type}_normalize_alternative_attributes_pre_save", $alternative['attributes'], $control['attributes'], $experiment, $alternative['id'] );
				return $alternative;
			},
			$alternatives
		);
	}

	/**
	 * Validates the attributes of an experiment variant.
	 *
	 * @param string                  $experiment_type Experiment type.
	 * @param 'control'|'alternative' $variant  Variant being validated.
	 * @param TAttributes             $attributes      Variant attributes.
	 * @param int                     $experiment_id   Experiment ID.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	public static function ai_validate( $experiment_type, $variant, $attributes, $experiment_id ) {
		$result = array(
			'status' => 'unsupported',
			'issues' => array(),
		);

		/**
		 * Filters the AI validation result for experiment variant attributes.
		 *
		 * @param TAttributes_Validation $validation    Default validation result.
		 * @param TAttributes            $attributes    Variant attributes to validate.
		 * @param int                    $experiment_id Experiment identifier.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_ai_validate_{$variant}_attributes", $result, $attributes, $experiment_id );
	}
}
