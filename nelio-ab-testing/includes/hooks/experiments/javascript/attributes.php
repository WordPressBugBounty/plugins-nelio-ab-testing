<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name' => '',
		'code' => '',
	);

	$alternative         = wp_parse_args( $alternative, $defaults );
	$alternative['name'] = trim( $alternative['name'] );
	$alternative['code'] = trim( $alternative['code'] );

	return $alternative;
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/javascript_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );

function set_default_snippet( $alternative ) {
	$alternative['code'] = sprintf(
		"utils.domReady( function() {\n\n  // %s\n\n  done();\n} );",
		_x( 'Write your code here…', 'user', 'nelio-ab-testing' )
	);
	return $alternative;
}//end set_default_snippet()
add_filter( 'nab_nab/javascript_create_alternative_content', __NAMESPACE__ . '\set_default_snippet' );
