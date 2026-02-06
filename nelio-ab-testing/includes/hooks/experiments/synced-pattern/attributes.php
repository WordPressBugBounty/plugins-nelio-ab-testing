<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TSynced_Pattern_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var TSynced_Pattern_Control_Attributes */
	$defaults = array(
		'patternId' => 0,
	);

	/** @var TSynced_Pattern_Control_Attributes */
	return wp_parse_args( $control, $defaults );
}
add_filter( 'nab_nab/synced-pattern_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TSynced_Pattern_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TSynced_Pattern_Alternative_Attributes */
	$defaults = array(
		'name'      => '',
		'patternId' => 0,
	);

	/** @var TSynced_Pattern_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/synced-pattern_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
