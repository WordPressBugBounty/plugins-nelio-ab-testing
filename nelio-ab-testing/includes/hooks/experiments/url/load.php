<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter( 'nab_has_nab/url_multi_url_alternative', '__return_true' );

function load_alternative( $alternative, $control, $experiment_id ) {
	$experiment   = nab_get_experiment( $experiment_id );
	$alternatives = $experiment->get_alternatives();
	$alternatives = wp_list_pluck( $alternatives, 'attributes' );
	$alternatives = wp_list_pluck( $alternatives, 'url' );
	add_filter( 'nab_alternative_urls', fn() => $alternatives );
}//end load_alternative()
add_action( 'nab_nab/url_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

function remove_protocol( $url ) {
	return preg_replace( '/^[^:]+:\/\//', '', $url );
}//end remove_protocol()

function remove_arguments( $url ) {
	return preg_replace( '/\?.*$/', '', $url );
}//end remove_arguments()
