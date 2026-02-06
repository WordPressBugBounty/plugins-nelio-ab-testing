<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter( 'nab_has_nab/url_multi_url_alternative', '__return_true' );

/**
 * Callback to add hooks to load alternative content.
 *
 * @param TUrl_Alternative_Attributes|TUrl_Control_Attributes $alternative   Alternative.
 * @param TUrl_Control_Attributes                             $control       Control.
 * @param int                                                 $experiment_id Experiment ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	assert( ! ( $experiment instanceof \WP_Error ) );
	$alternatives = $experiment->get_alternatives();
	$alternatives = wp_list_pluck( $alternatives, 'attributes' );
	$alternatives = wp_list_pluck( $alternatives, 'url' );
	add_filter( 'nab_alternative_urls', fn() => $alternatives );

	if ( ! empty( $control['useControlUrl'] ) ) {
		add_filter( 'nab_use_control_url_in_multi_url_alternative', '__return_true' );
	}
}
add_action( 'nab_nab/url_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

/**
 * Removes protocol from URL.
 *
 * @param string $url URL.
 *
 * @return string
 */
function remove_protocol( $url ) {
	$url = preg_replace( '/^[^:]+:\/\//', '', $url );
	return is_string( $url ) ? $url : '';
}

/**
 * Removes arguments from URL.
 *
 * @param string $url URL.
 *
 * @return string
 */
function remove_arguments( $url ) {
	$url = preg_replace( '/\?.*$/', '', $url );
	return is_string( $url ) ? $url : '';
}
