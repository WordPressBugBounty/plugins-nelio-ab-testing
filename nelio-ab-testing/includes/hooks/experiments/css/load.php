<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function strpos;

function encode_alternative( $alt ) {
	$name = nab_array_get( $alt, 'name', '' );

	$content_changes = nab_array_get( $alt, 'content', array() );
	$content_changes = is_array( $content_changes ) ? $content_changes : array();
	$content_changes = array_map( __NAMESPACE__ . '\get_content_change_snippet', $content_changes );
	$content_changes = implode( "\n", $content_changes );

	$css = nab_array_get( $alt, 'css', '' );
	$css = false === strpos( "$css", '</style>' ) ? $css : '';
	$css = nab_minify_css( $css );
	if ( ! empty( $css ) ) {
		// TOO DAVID. Append style.
		$css = sprintf( 'utils.appendStyle( %s );', wp_json_encode( $css ) );
	}//end if

	$code = sprintf(
		'function( done, utils ) {
			done();
			%1$s
			%2$s
		}',
		$content_changes,
		$css
	);
	$code = nab_minify_js( $code );
	return array(
		'name' => $name,
		'run'  => $code,
	);
}//end encode_alternative()
add_filter( 'nab_nab/css_get_alternative_summary', __NAMESPACE__ . '\encode_alternative' );

add_filter(
	'nab_nab/css_get_inline_settings',
	nab_return_constant(
		array(
			'load' => 'header',
			'mode' => 'script',
		)
	)
);

function get_content_change_snippet( $change ) {
	switch ( $change['type'] ) {
		case 'element':
			return sprintf(
				'utils.elementReady( %1$s, function( el ) {
					if ( utils.getCssPath( el ) !== %1$s ) {
						return;
					}
					el.innerHTML = %2$s;
				} );',
				wp_json_encode( $change['selector'] ),
				wp_json_encode( $change['html'] )
			);

		case 'image':
			return sprintf(
				'utils.elementReady( %1$s, function( el ) {
					if ( utils.getCssPath( el ) !== %1$s ) {
						return;
					}
					const src = %2$s;
					const alt = %3$s;
					if ( src ) {
						el.src = src;
						el.srcset = "";
						el.sizes = "";
					}
					if ( alt ) {
						el.alt = alt;
					}
				} );',
				wp_json_encode( $change['selector'] ),
				wp_json_encode( $change['src'] ),
				wp_json_encode( $change['alt'] )
			);

		default:
			return '';
	}//end switch
}//end get_content_change_snippet()
