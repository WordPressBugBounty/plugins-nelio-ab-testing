<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter( 'nab_has_nab/url_multi_url_alternative', '__return_true' );

function is_experiment_relevant( $value, $experiment_id, $url ) {
	$experiment = nab_get_experiment( $experiment_id );

	$alternative_urls = $experiment->get_alternatives();
	$alternative_urls = wp_list_pluck( $alternative_urls, 'attributes' );
	$alternative_urls = wp_list_pluck( $alternative_urls, 'url' );

	$is_relevant = array_reduce(
		$alternative_urls,
		function( $carry, $alternative_url ) use ( $url ) {
			return $carry || do_urls_match_exactly( $alternative_url, $url );
		},
		false
	);

	return $is_relevant || $value;
}//end is_experiment_relevant()
add_filter( 'nab_is_nab/url_relevant_in_url', __NAMESPACE__ . '\is_experiment_relevant', 10, 3 );

function load_alternative( $alternative, $control, $experiment_id ) {
	$experiment   = nab_get_experiment( $experiment_id );
	$alternatives = $experiment->get_alternatives();
	$alternatives = wp_list_pluck( $alternatives, 'attributes' );
	$alternatives = wp_list_pluck( $alternatives, 'url' );
	add_filter( 'nab_alternative_urls', fn() => $alternatives );
}//end load_alternative()
add_action( 'nab_nab/url_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

function do_urls_match_exactly( $actual_url, $expected_url ) {

	$actual_url   = strtolower( preg_replace( '/^[^:]+:\/\//', '', $actual_url ) );
	$expected_url = strtolower( preg_replace( '/^[^:]+:\/\//', '', $expected_url ) );

	$actual_args   = wp_parse_args( wp_parse_url( $actual_url, PHP_URL_QUERY ) );
	$expected_args = wp_parse_args( wp_parse_url( $expected_url, PHP_URL_QUERY ), $actual_args );

	ksort( $actual_args );
	ksort( $expected_args );

	$actual_url   = untrailingslashit( preg_replace( '/\?.*$/', '', $actual_url ) );
	$expected_url = untrailingslashit( preg_replace( '/\?.*$/', '', $expected_url ) );

	$actual_url   = add_query_arg( $actual_args, $actual_url );
	$expected_url = add_query_arg( $expected_args, $expected_url );

	return $actual_url === $expected_url;

}//end do_urls_match_exactly()
