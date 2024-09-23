<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'    => '',
		'themeId' => '',
	);
	return wp_parse_args( $alternative, $defaults );
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/theme_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
