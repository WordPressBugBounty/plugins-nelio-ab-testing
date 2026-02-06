<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TMenu_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var TMenu_Control_Attributes */
	$defaults = array(
		'menuId' => 0,
	);

	/** @var TMenu_Control_Attributes */
	return wp_parse_args( $control, $defaults );
}
add_filter( 'nab_nab/menu_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TMenu_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TMenu_Alternative_Attributes */
	$defaults = array(
		'name'   => '',
		'menuId' => 0,
	);

	/** @var TMenu_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/menu_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
