<?php
namespace Nelio_AB_Testing\Hooks\Cookie_Testing;

defined( 'ABSPATH' ) || exit;

/**
 * Sets testing globals.
 *
 * @return void
 */
function set_testing_globals() {
	if ( is_admin() ) {
		return; // @codeCoverageIgnore
	}

	if ( 'redirection' === nab_get_variant_loading_strategy() ) {
		return;
	}

	$settings = \Nelio_AB_Testing_Settings::instance();
	$cookie   = nab_get_cookie_alternative(
		array(
			'maxCombinations'     => nab_max_combinations(),
			'participationChance' => $settings->get( 'percentage_of_tested_visitors' ),
			'excludeBots'         => $settings->get( 'exclude_bots' ),
		)
	);

	if ( 'cookie-with-redirection-fallback' === nab_get_variant_loading_strategy() ) {
		$cookie = 0;
	}

	$post_request = (
		isset( $_SERVER['REQUEST_METHOD'] ) &&
		'POST' === $_SERVER['REQUEST_METHOD']
	);

	if ( $post_request ) {
		$_POST['nab'] = "$cookie";
	} else {
		$_GET['nab'] = "$cookie";
	}
	$_REQUEST['nab'] = "$cookie";

	if (
		'cookie' === nab_get_variant_loading_strategy() &&
		! isset( $_COOKIE['nabAlternative'] )
	) {
		nab_setcookie( 'nabAlternative', "$cookie", time() + 3 * MONTH_IN_SECONDS, '/' );
	}
	$_COOKIE['nabAlternative'] = "$cookie";
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\set_testing_globals', 5 );

/**
 * Adds filters to disable NAB settings when needed.
 *
 * @return void
 */
function disable_incompatible_plugin_settings() {
	if ( 'redirection' === nab_get_variant_loading_strategy() ) {
		return;
	}

	// INFO. When changing settings, update this file as well:
	// assets/src/admin/pages/settings/individual-settings/fields/alternative-loading-setting/index.tsx.

	$incompatible_setting_names = array(
		'match_all_segments',
		'preload_query_args',
	);

	if ( nab_are_participation_settings_disabled() ) {
		$incompatible_setting_names = array_merge(
			$incompatible_setting_names,
			array(
				'exclude_bots',
				'percentage_of_tested_visitors',
			)
		);
	}

	foreach ( $incompatible_setting_names as $name ) {
		add_filter( "nab_is_{$name}_setting_disabled", '__return_true', 999 );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\disable_incompatible_plugin_settings', 5 );
