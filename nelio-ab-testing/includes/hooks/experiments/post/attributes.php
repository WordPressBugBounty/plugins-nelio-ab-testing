<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $control Control attributes.
 *
 * @return TPost_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var TPost_Control_Attributes */
	$defaults = array(
		'postId'   => 0,
		'postType' => '',
	);

	/** @var TPost_Control_Attributes */
	$result = wp_parse_args( $control, $defaults );
	if ( empty( $result['testAgainstExistingContent'] ) || empty( $result['useControlUrl'] ) ) {
		unset( $result['useControlUrl'] );
	}

	return $result;
}
add_filter( 'nab_nab/page_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );
add_filter( 'nab_nab/post_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TPost_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TPost_Alternative_Attributes */
	$defaults = array(
		'name'   => '',
		'postId' => 0,
	);

	/** @var TPost_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/page_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/post_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
