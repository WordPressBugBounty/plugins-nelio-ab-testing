<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

function encode_alternative( $alt ) {
	$name = nab_array_get( $alt, 'name', '' );
	$code = nab_array_get( $alt, 'code', '' );
	$code = empty( $code ) ? 'done()' : $code;
	$code = sprintf( 'function(done,utils){%s}', $code );
	$code = nab_minify_js( $code );
	return array(
		'name' => $name,
		'run'  => $code,
	);
}//end encode_alternative()

add_filter(
	'nab_nab/javascript_get_alternative_summary',
	__NAMESPACE__ . '\encode_alternative'
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
