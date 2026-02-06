<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function strpos;

/**
 * Callback to encode alternative CSS and Text snippet.
 *
 * @param TCss_Control_Attributes|TCss_Alternative_Attributes $alt Alternative attributes.
 *
 * @return array{name:string,run:string}
 */
function encode_alternative( $alt ) {
	$name = $alt['name'] ?? '';

	$content_changes = $alt['content'] ?? array();
	$content_changes = array_map( __NAMESPACE__ . '\get_content_change_snippet', $content_changes );
	$content_changes = implode( "\n", $content_changes );

	$css = $alt['css'] ?? '';
	$css = false === strpos( "$css", '</style>' ) ? $css : '';
	$css = nab_minify_css( $css );
	if ( ! empty( $css ) ) {
		// TOO DAVID. Append style.
		$css = sprintf( 'utils.appendStyle( %s );', wp_json_encode( $css ) );
	}

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
}
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

/**
 * Converts the given content change into a runnable JS snippet.
 *
 * @param TCss_Content_Change $change Content change.
 *
 * @return string
 */
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
	}
}
