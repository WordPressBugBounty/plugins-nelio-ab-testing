<?php

defined( 'ABSPATH' ) || exit;

use function Nelio_AB_Testing\Experiment_Library\Php_Experiment\has_non_allowed_code;

function nab_eval_php( $code ) {
	if ( has_non_allowed_code( $code ) ) {
		throw new Nelio_AB_Testing_Php_Evaluation_Exception(
			sprintf(
				'the following code is not allowed: %s.',
				has_non_allowed_code( $code ) // phpcs:ignore
			)
		);
	}//end if
	return eval( $code ); // phpcs:ignore
}//end nab_eval_php()
