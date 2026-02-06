<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TWidget_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TWidget_Alternative_Attributes */
	$defaults = array(
		'name'     => '',
		'sidebars' => array(),
	);

	/** @var TWidget_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/widget_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
