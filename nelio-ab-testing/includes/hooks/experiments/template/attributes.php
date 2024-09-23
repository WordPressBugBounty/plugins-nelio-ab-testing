<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_control_attributes( $control ) {
	$defaults = array(
		'templateId' => '',
		'postType'   => '',
		'builder'    => '',
		'context'    => '',
	);
	return wp_parse_args( $control, $defaults );
}//end sanitize_control_attributes()
add_filter( 'nab_nab/template_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'       => '',
		'templateId' => '',
	);
	return wp_parse_args( $alternative, $defaults );
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/template_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
