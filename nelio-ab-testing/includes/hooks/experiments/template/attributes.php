<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TTemplate_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	if ( ! empty( $control['builder'] ) ) {
		/** @var TTemplate_Page_Builder_Control_Attributes */
		return array(
			'builder'    => $control['builder'],
			'context'    => $control['context'] ?? '',
			'templateId' => $control['templateId'] ?? '',
			'name'       => $control['name'] ?? '',
		);
	}

	/** @var TTemplate_Builtin_Control_Attributes */
	return array(
		'postType'   => $control['postType'] ?? '',
		'templateId' => $control['templateId'] ?? '',
		'name'       => $control['name'] ?? '',
	);
}
add_filter( 'nab_nab/template_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TTemplate_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TTemplate_Alternative_Attributes */
	$defaults = array(
		'name'       => '',
		'templateId' => '',
	);

	/** @var TTemplate_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/template_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
