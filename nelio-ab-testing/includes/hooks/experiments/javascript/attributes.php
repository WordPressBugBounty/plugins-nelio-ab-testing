<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TJavaScript_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var TJavaScript_Alternative_Attributes */
	$defaults = array(
		'name' => '',
		'code' => '',
	);

	/** @var TJavaScript_Alternative_Attributes */
	$alternative = wp_parse_args( $alternative, $defaults );

	$alternative['name'] = trim( $alternative['name'] );
	$alternative['code'] = trim( $alternative['code'] );

	return $alternative;
}
add_filter( 'nab_nab/javascript_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );

/**
 * Callback to set default snippet on alternative creation.
 *
 * @param TAttributes $alternative Attributes.
 *
 * @return TJavaScript_Alternative_Attributes
 */
function set_default_snippet( $alternative ) {
	$alternative['name'] = ! empty( $alternative['name'] ) && is_string( $alternative['name'] ) ? $alternative['name'] : '';
	$alternative['code'] = sprintf(
		"utils.domReady( function() {\n\n  // %s\n\n  done();\n} );",
		_x( 'Write your code here…', 'user', 'nelio-ab-testing' )
	);
	return $alternative;
}
add_filter( 'nab_nab/javascript_create_alternative_content', __NAMESPACE__ . '\set_default_snippet' );
