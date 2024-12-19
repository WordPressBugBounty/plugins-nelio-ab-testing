<?php
namespace Nelio_AB_Testing\Hooks\Cookie_Testing;

defined( 'ABSPATH' ) || exit;

function set_testing_cookies() {
	if ( is_admin() ) {
		return;
	}//end if

	if ( 'redirection' === nab_get_variant_loading_strategy() ) {
		return;
	}//end if

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
	}//end if

	$post_request = (
		isset( $_SERVER['REQUEST_METHOD'] ) &&
		'POST' === $_SERVER['REQUEST_METHOD']
	);

	if ( $post_request ) {
		$_POST['nab'] = $cookie;
	} else {
		$_GET['nab'] = $cookie;
	}//end if
	$_REQUEST['nab'] = $cookie;

	if (
		'cookie' === nab_get_variant_loading_strategy() &&
		! isset( $_COOKIE['nabAlternative'] )
	) {
		// phpcs:ignore
		setcookie( 'nabAlternative', $cookie, time() + 3 * MONTH_IN_SECONDS, '/' );
	}//end if
	// phpcs:ignore
	$_COOKIE['nabAlternative'] = $cookie;
}//end set_testing_cookies()
add_action( 'plugins_loaded', __NAMESPACE__ . '\set_testing_cookies', 5 );

function disable_incompatible_plugin_settings() {
	if ( ! is_admin() ) {
		return;
	}//end if

	if ( 'redirection' === nab_get_variant_loading_strategy() ) {
		return;
	}//end if

	// NOTE. When changing settings, update this file as well:
	// assets/src/admin/pages/settings/individual-settings/fields/alternative-loading-setting/index.tsx.

	$incompatible_settings = array(
		'match_all_segments',
		'preload_query_args',
	);

	if ( nab_are_participation_settings_disabled() ) {
		$incompatible_settings = array_merge(
			$incompatible_settings,
			array(
				'exclude_bots',
				'percentage_of_tested_visitors',
			)
		);
	}//end if

	foreach ( $incompatible_settings as $s ) {
		add_filter(
			'nab_is_setting_disabled',
			fn( $d, $n ) => $s === $n ? true : $d,
			999,
			2
		);
	}//end foreach
}//end disable_incompatible_plugin_settings()
add_action( 'plugins_loaded', __NAMESPACE__ . '\disable_incompatible_plugin_settings', 5 );
