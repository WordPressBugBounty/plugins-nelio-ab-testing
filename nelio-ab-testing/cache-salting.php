<?php

defined( 'WPINC' ) || die();

require_once __DIR__ . '/includes/utils/functions/cookie-testing.php';

/**
 * Returns a short string that can be used to salt the cache for a visitor.
 *
 * @param array $settings Optional. An array of (optional) settings. Allowed settings:
 *        - maxCombinations: an integer that specifies the maximum number of combinations allowed (2 to 24). Default: 24.
 *        - participationChance: an integer from 1 to 100. Default: 100.
 *        - excludeBots: a boolean or a function that, given a user agent, returns a boolean. Default: false.
 *
 * @return string a salting string.
 */
function nab_get_cache_salt( $settings = array() ): string {
	$alternative = nab_get_cookie_alternative( $settings );
	if ( ! isset( $_COOKIE['nabAlternative'] ) ) {
		// phpcs:ignore
		$_COOKIE['nabAlternative'] = $alternative;
		// phpcs:ignore
		setcookie( 'nabAlternative', $alternative, time() + 3 * MONTH_IN_SECONDS, '/' );
	}//end if
	return 'none' === $alternative
		? 'NAB-NO'
		: sprintf( 'NAB-%02d', abs( (int) $alternative ) );
}//end nab_get_cache_salt()
