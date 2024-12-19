<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

function encode_alternative( $alt ) {
	if ( empty( $alt ) ) {
		return '{"name":"","run":function(){}}';
	}//end if

	$name = trim( nab_array_get( $alt, array( 'attributes', 'name' ), '' ) );
	$code = trim( nab_array_get( $alt, array( 'attributes', 'code' ), '' ) );
	$code = trim( $code );
	$code = empty( $code ) ? 'done()' : $code;
	$code = nab_minify_js( $code );
	$code = sprintf( 'function(done,utils){%s}', $code );
	return sprintf(
		'{"name":%s,"run":%s}',
		wp_json_encode( $name ),
		$code
	);
}//end encode_alternative()

add_filter(
	'nab_nab/javascript_encode_alternatives_in_main_script',
	function ( $_, $experiment ) {
		$alternatives = array_map( __NAMESPACE__ . '\encode_alternative', $experiment->get_alternatives() );
		$alternatives = implode( ',', $alternatives );
		return "[{$alternatives}]";
	},
	10,
	2
);

add_filter(
	'nab_nab/javascript_get_inline_settings',
	nab_return_constant(
		array(
			'load' => 'header',
			'mode' => 'script',
		)
	)
);
