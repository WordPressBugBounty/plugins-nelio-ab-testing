<?php

defined( 'ABSPATH' ) || exit;

use function Nelio_AB_Testing\Experiment_Library\Php_Experiment\has_non_allowed_code;

/**
 * Evaluates the givne PHP code.
 *
 * @param string $code Code.
 *
 * @return mixed
 *
 * @throws Nelio_AB_Testing_Php_Evaluation_Exception If code contains invalid snippets.
 * @throws \ParseError If code does not parse.
 * @throws \Error If code fails.
 */
function nab_eval_php( $code ) {
	if ( has_non_allowed_code( $code ) ) {
		throw new Nelio_AB_Testing_Php_Evaluation_Exception(
			sprintf(
				'the following code is not allowed: %s.',
				esc_html( has_non_allowed_code( $code ) )
			)
		);
	}
	// phpcs:ignore Generic.PHP.ForbiddenFunctions.Found, Squiz.PHP.Eval.Discouraged
	return eval( $code );
}
