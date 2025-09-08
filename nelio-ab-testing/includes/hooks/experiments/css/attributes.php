<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_alternative_attributes( $alternative ) {
	$defaults    = array(
		'name'    => '',
		'css'     => '',
		'content' => array(),
	);
	$alternative = wp_parse_args( $alternative, $defaults );

	$alternative['content'] = array_filter(
		$alternative['content'],
		fn( $cv ) => (
			'element' !== $cv['type'] ||
			trim( wp_strip_all_tags( (string) nab_array_get( $cv, 'html' ) ) )
		)
	);
	$alternative['content'] = array_filter(
		$alternative['content'],
		fn( $cv ) => (
			'image' !== $cv['type'] ||
			trim( (string) nab_array_get( $cv, 'alt' ) ) ||
			trim( (string) nab_array_get( $cv, 'src' ) )
		)
	);
	$alternative['content'] = array_values( $alternative['content'] );

	return $alternative;
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/css_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
