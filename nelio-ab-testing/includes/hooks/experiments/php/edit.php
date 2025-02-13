<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return false;
	}//end if

	return add_query_arg(
		array(
			'page'        => 'nelio-ab-testing-php-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}//end get_edit_link()
add_filter( 'nab_nab/php_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-php-experiment-admin', 'php-experiment-admin', true );

	wp_register_style(
		'nab-php-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/php-experiment-admin.css',
		array( 'wp-admin', 'wp-components' ),
		nelioab()->plugin_version
	);
}//end register_admin_assets()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_assets' );

function register_public_assets() {

	nab_register_script_with_auto_deps( 'nab-php-experiment-public', 'php-experiment-public', true );

	wp_register_style(
		'nab-php-experiment-public',
		nelioab()->plugin_url . '/assets/dist/php/php-experiment-public.css',
		array(),
		nelioab()->plugin_version
	);
}//end register_public_assets()
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_public_assets' );

function maybe_load_php_previewer() {
	if ( ! isset( $_GET['nab-php-previewer'] ) ) { // phpcs:ignore
		return;
	}//end if
	add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore
	wp_enqueue_style( 'nab-php-experiment-public' );
	wp_enqueue_script( 'nab-php-experiment-public' );
	wp_add_inline_script( 'nab-php-experiment-public', 'nab.initPhpPreviewer()' );
}//end maybe_load_php_previewer()
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_load_php_previewer' );

function add_php_editor_page() {
	$page = new Nelio_AB_Testing_Php_Editor_Page();
	$page->init();
}//end add_php_editor_page()
add_filter( 'admin_menu', __NAMESPACE__ . '\add_php_editor_page' );

function should_split_testing_be_disabled( $disabled ) {
	if ( isset( $_GET['nab-php-previewer'] ) ) { // phpcs:ignore
		return true;
	}//end if
	return $disabled;
}//end should_split_testing_be_disabled()
add_filter( 'nab_disable_split_testing', __NAMESPACE__ . '\should_split_testing_be_disabled' );
