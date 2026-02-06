<?php

/**
 * Returns the variant loading strategy.
 *
 * - `redirection`: Default behavior. Alternative content will be
 *      loaded using query arguments added via a JavaScript redirection.
 * - `cookie`: The plugin will load the appropriate variant by looking
 *      at the `nabAlternative` cookie. If said cookie doesn’t exist,
 *      it’ll be created when the plugin kicks in.
 * - `cookie-with-redirection-fallback`: It’s equivalent to the previous
 *      strategy, but it’ll behave as `redirection` when `nabAlternative`
 *      is not available.
 *
 * @return 'redirection'|'cookie'|'cookie-with-redirection-fallback' the variant loading strategy.
 *
 * @since 7.0.0
 */
function nab_get_variant_loading_strategy() {
	/** @var 'redirection'|'cookie'|'cookie-with-redirection-fallback'|null */
	static $result;
	if ( ! empty( $result ) ) {
		return $result;
	}

	$run = function () {
		$settings = Nelio_AB_Testing_Settings::instance();
		$setting  = $settings->get( 'alternative_loading' );
		$mode     = $setting['mode'];

		if ( 'redirection' === $mode ) {
			return 'redirection';
		}

		if ( isset( $_COOKIE['nabAlternative'] ) ) {
			return 'cookie';
		}

		return empty( $setting['redirectIfCookieIsMissing'] )
			? 'cookie'
			: 'cookie-with-redirection-fallback';
	};

	$result = $run();
	return $result;
}

/**
 * Returns whether participation settings are disabled or not.
 *
 * If they’re disabled, the visitor can’t edit them via the settings UI and should reproduce their regular behavior via code.
 *
 * @return boolean whether participation settings are disabled or not.
 *
 * @since 7.0.0
 */
function nab_are_participation_settings_disabled() {
	$settings = Nelio_AB_Testing_Settings::instance();
	$setting  = $settings->get( 'alternative_loading' );
	return ! empty( $setting['lockParticipationSettings'] );
}

/**
 * Returns the variant the visitor is supposed to see.
 *
 * @param array{maxCombinations?:int,participationChance?:int,excludeBots?:bool|callable(string):bool} $settings Optional. An array of settings. Allowed settings:
 *        - maxCombinations: an integer that specifies the maximum number of combinations allowed (2 to 24). Default: 24.
 *        - participationChance: an integer from 1 to 100. Default: 100.
 *        - excludeBots: a boolean or a function that, given a user agent, returns a boolean. Default: false.
 *
 * @return string|int either the string `none` or a number from 0 to max combinations.
 */
function nab_get_cookie_alternative( $settings = array() ) {
	$max_combinations = isset( $settings['maxCombinations'] ) ? $settings['maxCombinations'] : 24;
	$max_combinations = max( 2, $max_combinations );

	$participation_chance = isset( $settings['participationChance'] ) ? $settings['participationChance'] : 100;
	$participation_chance = max( 1, min( 100, $participation_chance ) );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$user_agent   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$user_agent   = is_string( $user_agent ) ? $user_agent : '';
	$exclude_bots = isset( $settings['excludeBots'] ) ? $settings['excludeBots'] : false;
	$is_bot       = is_callable( $exclude_bots )
		? fn() => true === call_user_func( $exclude_bots, $user_agent )
		: fn() => ! empty( preg_match( '/bot|spider|crawl|http|lighthouse/i', $user_agent ) );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$alternative = isset( $_COOKIE['nabAlternative'] ) ? $_COOKIE['nabAlternative'] : false;
	if ( false === $alternative ) {
		$alternative = ! $is_bot() && random_int( 0, 100 ) <= $participation_chance
			? random_int( 0, $max_combinations - 1 )
			: 'none';
	}

	if ( 'none' !== $alternative ) {
		$alternative = abs( intval( is_numeric( $alternative ) ? $alternative : '0' ) );
		$alternative = min( $alternative, $max_combinations );
	}

	return $alternative;
}
