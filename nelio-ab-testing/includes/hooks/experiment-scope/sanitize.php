<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Sanitize;

defined( 'ABSPATH' ) || exit;

function sanitize_experiment_scope( $scope, $experiment ) {
	$scope = array_map(
		function ( $rule ) use ( $experiment ) {
			switch ( nab_array_get( $rule, 'attributes.type' ) ) {
				case 'exact':
				case 'different':
				case 'partial':
				case 'partial-not-included':
					return sanitize_custom_url_scope( $rule );

				case 'tested-url-with-query-args':
					return sanitize_tested_url_with_query_args( $rule, $experiment );

				default:
					return $rule;
			}//end switch
		},
		$scope
	);

	return array_values( array_filter( $scope ) );
}//end sanitize_experiment_scope()
add_filter( 'nab_sanitize_experiment_scope', __NAMESPACE__ . '\sanitize_experiment_scope', 5, 2 );

function sanitize_custom_url_scope( $rule ) {
	$value = nab_array_get( $rule, 'attributes.value' );
	$value = is_string( $value ) ? $value : '';
	$value = trim( $value );
	if ( empty( $value ) ) {
		return false;
	}//end if

	$rule['attributes']['value'] = $value;
	return $rule;
}//end sanitize_custom_url_scope()

function sanitize_tested_url_with_query_args( array $rule, \Nelio_AB_Testing_Experiment $experiment ) {
	$args = nab_array_get( $rule, 'attributes.value.args' );
	$args = is_array( $args ) ? $args : array();
	$args = array_filter( $args, fn( $q ) => ! empty( $q['name'] ) );
	$args = array_values( $args );

	$urls = $experiment->get_alternatives();
	$urls = array_map( fn( $a ) => nab_array_get( $a, 'attributes.url', '' ), $urls );
	$urls = array_values( array_filter( $urls ) );

	$rule['attributes']['value'] = array(
		'args' => $args,
		'urls' => $urls,
	);

	return $rule;
}//end sanitize_tested_url_with_query_args()