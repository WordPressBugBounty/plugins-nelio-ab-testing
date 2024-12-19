<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function strpos;

function load_alternative( $_, $__, $experiment_id ) {

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return;
	}//end if

	add_action(
		'wp_head',
		function () use ( &$experiment ) {
			$alternatives = array_values( $experiment->get_alternatives() );
			$alternatives = array_map(
				function ( $alt_id, $alt ) use ( &$experiment ) {
					$exp_id = $experiment->get_id();
					$css    = nab_array_get( $alt, array( 'attributes', 'css' ), '' );
					$css    = false === strpos( "$css", '</style>' ) ? $css : '';
					$css    = nab_minify_css( $css );
					if ( empty( $css ) ) {
						return '';
					}//end if

					$css = sprintf( '<style type="text/css">%s</style>', $css );
					$css = sprintf( '<noscript class="nab-exp-%d nab-alt-%d">%s</noscript>', $exp_id, $alt_id, $css );
					return $css;
				},
				array_keys( $alternatives ),
				$alternatives
			);
			nab_print_html( sprintf( "\n%s\n", implode( "\n", array_filter( $alternatives ) ) ) );
		},
		9999
	);
}//end load_alternative()
add_action( 'nab_nab/css_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

add_filter(
	'nab_nab/css_get_alternative_summary',
	function ( $attrs ) {
		unset( $attrs['css'] );
		return $attrs;
	}
);

add_filter(
	'nab_nab/css_get_inline_settings',
	nab_return_constant(
		array(
			'load' => 'header',
			'mode' => 'unwrap',
		)
	)
);
