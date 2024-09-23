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


function flush_cache() {
	if ( function_exists( 'flush_wp_rocket' ) ) {
		flush_wp_rocket();
	}//end if
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}//end if
}//end flush_cache()
add_action( 'nab_flush_all_caches', __NAMESPACE__ . '\flush_cache' );


function maybe_ignore_nab_arg_during_cookie_testing( $args ) {
	return is_cookie_testing_enabled()
		? ignore_nab_arg_during_cookie_testing( $args )
		: $args;
}//end maybe_ignore_nab_arg_during_cookie_testing()
add_filter( 'rocket_cache_ignored_parameters', __NAMESPACE__ . '\maybe_ignore_nab_arg_during_cookie_testing', 999 );


function maybe_add_nab_alternative_as_dynamic_cookie( $cookies ) {
	return is_cookie_testing_enabled()
		? add_nab_alternative_as_dynamic_cookie( $cookies )
		: $cookies;
}//end maybe_add_nab_alternative_as_dynamic_cookie()
add_filter( 'rocket_cache_dynamic_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );
add_filter( 'rocket_cache_mandatory_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );


function exclude_files( $excluded_files = array() ) {
	$excluded_files[] = 'nelio-ab-testing';
	$excluded_files[] = 'nab';
	return $excluded_files;
}//end exclude_files()
add_filter( 'rocket_delay_js_exclusions', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_defer_js', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_async_css', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_cache_busting', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_static_dynamic_resources', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_excluded_inline_js_content', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_js', __NAMESPACE__ . '\exclude_files', 10, 1 );


function regenerate_config_on_nab_install() {
	if ( is_cookie_testing_enabled() ) {
		regenerate_config( 'cookie-testing' );
	} else {
		regenerate_config( 'redirection' );
	}//end if
}//end regenerate_config_on_nab_install()
add_action( 'nab_installed', __NAMESPACE__ . '\regenerate_config_on_nab_install' );


function regenerate_config_on_nab_uninstall() {
	regenerate_config( 'redirection' );
}//end regenerate_config_on_nab_uninstall()
add_action( 'nab_uninstalled', __NAMESPACE__ . '\regenerate_config_on_nab_uninstall' );


function regenerate_config_on_option_update( $option, $old_value, $value ) {
	if ( 'nelio-ab-testing_settings' !== $option ) {
		return;
	}//end if

	$old_value = nab_array_get( $old_value, 'alternative_loading.mode' );
	$value     = nab_array_get( $value, 'alternative_loading.mode' );
	if ( $old_value === $value ) {
		return;
	}//end if

	if ( 'cookie' === $value ) {
		regenerate_config( 'cookie-testing' );
	} else {
		regenerate_config( 'redirection' );
	}//end if
}//end regenerate_config_on_option_update()
add_action( 'update_option', __NAMESPACE__ . '\regenerate_config_on_option_update', 10, 3 );

// =======
// HELPERS
// =======

function is_cookie_testing_enabled() {
	$option = get_option( 'nelio-ab-testing_settings' );
	return 'cookie' === nab_array_get( $option, 'alternative_loading.mode' );
}//end is_cookie_testing_enabled()


function regenerate_config( $cookie_testing ) {
	remove_filter( 'rocket_cache_ignored_parameters', __NAMESPACE__ . '\maybe_ignore_nab_arg_during_cookie_testing', 999 );
	remove_filter( 'rocket_cache_dynamic_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );
	remove_filter( 'rocket_cache_mandatory_cookies', __NAMESPACE__ . '\maybe_add_nab_alternative_as_dynamic_cookie' );

	if ( 'cookie-testing' === $cookie_testing ) {
		add_filter( 'rocket_cache_ignored_parameters', __NAMESPACE__ . '\ignore_nab_arg_during_cookie_testing', 999 );
		add_filter( 'rocket_cache_dynamic_cookies', __NAMESPACE__ . '\add_nab_alternative_as_dynamic_cookie' );
		add_filter( 'rocket_cache_mandatory_cookies', __NAMESPACE__ . '\add_nab_alternative_as_dynamic_cookie' );
	}//end if

	if ( function_exists( 'rocket_generate_config_file' ) ) {
		rocket_generate_config_file();
	}//end if
	flush_cache();
}//end regenerate_config()


function ignore_nab_arg_during_cookie_testing( $args ) {
	$args['nab']        = 1;
	$args['nabforce']   = 1;
	$args['nabstaging'] = 1;
	return $args;
}//end ignore_nab_arg_during_cookie_testing()


function add_nab_alternative_as_dynamic_cookie( $cookies ) {
	$cookies[] = 'nabAlternative';
	return $cookies;
}//end add_nab_alternative_as_dynamic_cookie()
