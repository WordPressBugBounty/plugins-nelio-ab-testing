<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                        $edit_link      Edit link.
 * @param TPhp_Alternative_Attributes|TPhp_Control_Attributes $alternative    Alternative.
 * @param TPhp_Control_Attributes                             $control        Control.
 * @param int                                                 $experiment_id  Experiment ID.
 * @param string                                              $alternative_id Alternative ID.
 *
 * @return string|false
 */
function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return false;
	}

	return add_query_arg(
		array(
			'page'        => 'nelio-ab-testing-php-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}
add_filter( 'nab_nab/php_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

/**
 * Callback to register admin assets.
 *
 * @return void
 */
function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-php-experiment-admin', 'php-experiment-admin', true );

	wp_register_style(
		'nab-php-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/php-experiment-admin.css',
		array( 'wp-admin', 'wp-components' ),
		nelioab()->plugin_version
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_assets' );

/**
 * Callback to register public assets.
 *
 * @return void
 */
function register_public_assets() {

	nab_register_script_with_auto_deps( 'nab-php-experiment-public', 'php-experiment-public', true );

	wp_register_style(
		'nab-php-experiment-public',
		nelioab()->plugin_url . '/assets/dist/php/php-experiment-public.css',
		array(),
		nelioab()->plugin_version
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_public_assets' );

/**
 * Callback to enqueue required scripts and styles to preview PHP tests.
 *
 * @return void
 */
function maybe_load_php_previewer() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-php-previewer'] ) ) {
		return;
	}
	// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
	add_filter( 'show_admin_bar', '__return_false' );
	wp_enqueue_style( 'nab-php-experiment-public' );
	wp_enqueue_script( 'nab-php-experiment-public' );
	wp_add_inline_script( 'nab-php-experiment-public', 'nab.initPhpPreviewer()' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_load_php_previewer' );

/**
 * Callback to register the PHP Editor page in the Dashboard.
 *
 * @return void
 */
function add_php_editor_page() {
	$page = new Nelio_AB_Testing_Php_Editor_Page();
	$page->init();
}
add_action( 'admin_menu', __NAMESPACE__ . '\add_php_editor_page' );

/**
 * Callback to disable split testing while previewing PHP tests.
 *
 * @param bool $disabled Disabled.
 *
 * @return bool
 */
function should_split_testing_be_disabled( $disabled ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['nab-php-previewer'] ) ) {
		return true;
	}
	return $disabled;
}
add_filter( 'nab_disable_split_testing', __NAMESPACE__ . '\should_split_testing_be_disabled' );
