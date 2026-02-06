<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TPopup_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var TPopup_Control_Attributes */
	$defaults = array(
		'postId'   => 0,
		'postType' => '',
	);

	/** @var TPopup_Control_Attributes */
	return wp_parse_args( $control, $defaults );
}
add_filter( 'nab_nab/popup_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TPopup_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TPopup_Alternative_Attributes */
	$defaults = array(
		'postId' => 0,
	);

	/** @var TPopup_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/popup_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
