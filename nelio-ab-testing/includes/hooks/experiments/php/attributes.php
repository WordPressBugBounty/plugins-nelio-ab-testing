<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TPhp_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'            => Z::string()->trim()->catch( '' ),
				'snippet'         => Z::string()->trim()->catch( '' ),
				'validateSnippet' => Z::boolean()->optional(),
				'errorMessage'    => Z::string()->optional(),
				'warningMessage'  => Z::string()->optional(),
			)
		)->catch(
			array(
				'name'    => '',
				'snippet' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TPhp_Alternative_Attributes */
	$alternative = $parsed['data'];

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
		}
	}

	return $alternative;
}
add_filter( 'nab_nab/php_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );

/**
 * Returns the non-allowed snippets of code found in the given code or `false` otherwise.
 *
 * @param string $code Code.
 *
 * @return string|false
 */
function has_non_allowed_code( $code ) {
	if ( preg_match( '/(base64_decode|error_reporting|ini_set|eval)\s*\(/i', $code, $matches ) ) {
		return trim( $matches[1] );
	}

	$matches = array();
	if ( preg_match( '/dns_get_record/i', $code, $matches ) ) {
		return trim( $matches[0] );
	}

	return false;
}
