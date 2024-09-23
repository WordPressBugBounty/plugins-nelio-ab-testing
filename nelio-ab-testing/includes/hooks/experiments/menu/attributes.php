<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_control_attributes( $control ) {
	$defaults = array(
		'menuId' => 0,
	);
	return wp_parse_args( $control, $defaults );
}//end sanitize_control_attributes()
add_filter( 'nab_nab/menu_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'   => '',
		'menuId' => 0,
	);
	return wp_parse_args( $alternative, $defaults );
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/menu_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
