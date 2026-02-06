<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to encode alternative JavaScript snippet.
 *
 * @param TJavaScript_Control_Attributes|TJavaScript_Alternative_Attributes $alt Alternative attributes.
 *
 * @return array{name:string,run:string}
 */
function encode_alternative( $alt ) {
	$name = $alt['name'] ?? '';
	$code = $alt['code'] ?? '';
	$code = empty( $code ) ? 'done()' : $code;
	$code = "{$code}\n";
	$code = sprintf( 'function(done,utils){%s}', $code );
	$code = nab_minify_js( $code );
	return array(
		'name' => $name,
		'run'  => $code,
	);
}

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
