<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return THeadline_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var THeadline_Control_Attributes */
	$defaults = array(
		'postId'   => 0,
		'postType' => 'post',
	);

	/** @var THeadline_Control_Attributes */
	$control = wp_parse_args( $control, $defaults );
	return $control;
}
add_filter( 'nab_nab/headline_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return THeadline_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var THeadline_Alternative_Attributes */
	$defaults = array(
		'name'     => '',
		'excerpt'  => '',
		'imageId'  => 0,
		'imageUrl' => '',
	);

	/** @var THeadline_Alternative_Attributes */
	$alternative = wp_parse_args( $alternative, $defaults );
	return $alternative;
}
add_filter( 'nab_nab/headline_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
