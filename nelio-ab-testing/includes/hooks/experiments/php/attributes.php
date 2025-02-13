<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_alternative_attributes( $alternative ) {
	$defaults    = array(
		'name'    => '',
		'snippet' => '',
	);
	$alternative = wp_parse_args( $alternative, $defaults );

	if ( isset( $alternative['validateSnippet'] ) ) {
		unset( $alternative['validateSnippet'] );
		unset( $alternative['errorMessage'] );
		unset( $alternative['warningMessage'] );
		try {
			nab_eval_php( $alternative['snippet'] );
		} catch ( \Nelio_AB_Testing_Php_Evaluation_Exception $e ) {
			$alternative['errorMessage'] = $e->getMessage();
		} catch ( \ParseError $e ) {
			$alternative['errorMessage'] = $e->getMessage();
		} catch ( \Error $e ) {
			$alternative['warningMessage'] = $e->getMessage();
		}//end try
	}//end if

	return $alternative;
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/php_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );

function has_non_allowed_code( $code ) {
	if ( preg_match( '/(base64_decode|error_reporting|ini_set|eval)\s*\(/i', $code, $matches ) ) {
		return trim( $matches[1] );
	}//end if

	$matches = array();
	if ( preg_match( '/dns_get_record/i', $code, $matches ) ) {
		return trim( $matches[0] );
	}//end if

	return false;
}//end has_non_allowed_code()
