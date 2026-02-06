<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TTheme_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TTheme_Alternative_Attributes */
	$defaults = array(
		'name'    => '',
		'themeId' => '',
	);

	/** @var TTheme_Alternative_Attributes */
	return wp_parse_args( $alternative, $defaults );
}
add_filter( 'nab_nab/theme_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
