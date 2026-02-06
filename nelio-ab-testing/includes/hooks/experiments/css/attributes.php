<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TCss_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'    => '',
		'css'     => '',
		'content' => array(),
	);
	/** @var TCss_Alternative_Attributes $alternative */
	$alternative = wp_parse_args( $alternative, $defaults );

	$alternative['content'] = array_filter(
		$alternative['content'] ?? array(),
		fn( $cv ) => (
			'element' !== $cv['type'] ||
			trim( wp_strip_all_tags( $cv['html'] ) )
		)
	);
	$alternative['content'] = array_filter(
		$alternative['content'],
		fn( $cv ) => (
			'image' !== $cv['type'] ||
			trim( $cv['alt'] ) ||
			trim( $cv['src'] )
		)
	);
	$alternative['content'] = array_values( $alternative['content'] );

	return $alternative;
}
add_filter( 'nab_nab/css_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
