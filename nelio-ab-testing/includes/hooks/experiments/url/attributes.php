<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_attributes( $control ) {
	$defaults = array(
		'url' => '',
	);
	return wp_parse_args( $control, $defaults );
}//end sanitize_attributes()
add_filter( 'nab_nab/url_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_attributes' );
add_filter( 'nab_nab/url_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_attributes' );
