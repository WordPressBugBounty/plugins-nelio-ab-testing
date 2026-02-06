<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with WPRocket.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      5.0.6
 */

namespace Nelio_AB_Testing\Compat\Cache\WPRocket;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes cache.
 *
 * @return void
 */
function flush_cache() {
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );

/**
 * Ignores nab arg during cookie testing.
 *
 * @param array<string,1> $args Ignored args.
 *
 * @return array<string,1>
 */
function maybe_ignore_nab_arg_during_cookie_testing( $args ) {
	return is_cookie_testing_enabled()
		? ignore_nab_arg_during_cookie_testing( $args )
		: $args;
}
add_filter( 'rocket_cache_ignored_parameters', __NAMESPACE__ . '\maybe_ignore_nab_arg_during_cookie_testing', 999 );

/**
 * Callback to add nabAlternative as a dynamic cookie, if cookie testing is enabled.
 *
 * @param list<string> $cookies Cookies.
 *
 * @return list<string>
 */
function maybe_add_nab_alternative_as_dynamic_cookie( $cookies ) {
	return is_cookie_testing_enabled()
		? add_nab_alternative_as_dynamic_cookie( $cookies )
		: $cookies;
}
add_filter( 'rocket_cache_dynamic_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );
add_filter( 'rocket_cache_mandatory_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );

/**
 * List of files excluded from any optimization process.
 *
 * @param list<string> $excluded_files Files.
 *
 * @return list<string>
 */
function exclude_files( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing';
	$excluded_files[] = 'nab';
	return $excluded_files;
}
add_filter( 'rocket_delay_js_exclusions', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_defer_js', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_async_css', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_cache_busting', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_static_dynamic_resources', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_excluded_inline_js_content', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_js', __NAMESPACE__ . '\exclude_files', 10, 1 );

/**
 * Callback to regenerate config file when plugin is activated.
 *
 * @return void
 */
function regenerate_config_on_nab_install() {
	if ( is_cookie_testing_enabled() ) {
		regenerate_config( 'cookie-testing' );
	} else {
		regenerate_config( 'redirection' );
	}
}
add_action( 'nab_installed', __NAMESPACE__ . '\regenerate_config_on_nab_install' );

/**
 * Callback to regenerate config file when plugin is deactivated.
 *
 * @return void
 */
function regenerate_config_on_nab_uninstall() {
	regenerate_config( 'redirection' );
}
add_action( 'nab_uninstalled', __NAMESPACE__ . '\regenerate_config_on_nab_uninstall' );

/**
 * Callback to regenerate config file on option update.
 *
 * @param string                                          $option    Option name.
 * @param array{alternative_loading?:array{mode?:string}} $old_value Old value.
 * @param array{alternative_loading?:array{mode?:string}} $value     Value.
 *
 * @return void
 */
function regenerate_config_on_option_update( $option, $old_value, $value ) {
	if ( 'nelio-ab-testing_settings' !== $option ) {
		return;
	}

	$old_value = $old_value['alternative_loading']['mode'] ?? 'redirection';
	$value     = $value['alternative_loading']['mode'] ?? 'redirection';
	if ( $old_value === $value ) {
		return;
	}

	if ( 'cookie' === $value ) {
		regenerate_config( 'cookie-testing' );
	} else {
		regenerate_config( 'redirection' );
	}
}
add_action( 'update_option', __NAMESPACE__ . '\regenerate_config_on_option_update', 10, 3 );

// =======
// HELPERS
// =======

/**
 * Whether cookie testing is enabled or not.
 *
 * @return bool
 */
function is_cookie_testing_enabled() {
	/** @var array{alternative_loading?:array{mode?:string}} */
	$option = get_option( 'nelio-ab-testing_settings' );
	$mode   = $option['alternative_loading']['mode'] ?? 'redirection';
	return 'cookie' === $mode;
}

/**
 * Regenerates config file.
 *
 * @param 'cookie-testing'|'redirection' $mode Mode.
 *
 * @return void
 */
function regenerate_config( $mode ) {
	remove_filter( 'rocket_cache_ignored_parameters', __NAMESPACE__ . '\maybe_ignore_nab_arg_during_cookie_testing', 999 );
	remove_filter( 'rocket_cache_dynamic_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );
	remove_filter( 'rocket_cache_mandatory_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );

	if ( 'cookie-testing' === $mode ) {
		add_filter( 'rocket_cache_ignored_parameters', __NAMESPACE__ . '\ignore_nab_arg_during_cookie_testing', 999 );
		add_filter( 'rocket_cache_dynamic_cookies', __NAMESPACE__ . '\add_nab_alternative_as_dynamic_cookie' );
		add_filter( 'rocket_cache_mandatory_cookies', __NAMESPACE__ . '\add_nab_alternative_as_dynamic_cookie' );
	}

	if ( function_exists( 'rocket_generate_config_file' ) ) {
		rocket_generate_config_file();
	}
	flush_cache();
}

/**
 * Adds nab arguments to list of args.
 *
 * @param array<string,1> $args Args.
 *
 * @return array<string,1>
 */
function ignore_nab_arg_during_cookie_testing( $args ) {
	$args['nab']        = 1;
	$args['nabforce']   = 1;
	$args['nabstaging'] = 1;
	return $args;
}

/**
 * Adds `nabAlternative` to a list of cookies.
 *
 * @param list<string> $cookies Cookies.
 *
 * @return list<string>
 */
function add_nab_alternative_as_dynamic_cookie( $cookies ) {
	$cookies[] = 'nabAlternative';
	return $cookies;
}
