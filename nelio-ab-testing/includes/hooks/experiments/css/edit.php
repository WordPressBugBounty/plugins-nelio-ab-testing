<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return false;
	}//end if

	return add_query_arg(
		array(
			'page'        => 'nelio-ab-testing-css-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}//end get_edit_link()
add_filter( 'nab_nab/css_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-css-experiment-admin', 'css-experiment-admin', true );

	wp_register_style(
		'nab-css-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/css-experiment-admin.css',
		array( 'wp-admin', 'wp-components' ),
		nelioab()->plugin_version
	);
}//end register_admin_assets()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_assets' );

function register_public_assets() {

	nab_register_script_with_auto_deps( 'nab-css-experiment-public', 'css-experiment-public', true );

	wp_register_style(
		'nab-css-experiment-public',
		nelioab()->plugin_url . '/assets/dist/css/css-experiment-public.css',
		array(),
		nelioab()->plugin_version
	);
}//end register_public_assets()
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_public_assets' );

function maybe_load_css_previewer() {
	if ( ! isset( $_GET['nab-css-previewer'] ) ) { // phpcs:ignore
		return;
	}//end if
	add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore
	wp_enqueue_style( 'nab-css-experiment-public' );
	wp_enqueue_script( 'nab-css-experiment-public' );
	wp_add_inline_script( 'nab-css-experiment-public', 'nab.initCssPreviewer()' );
}//end maybe_load_css_previewer()
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_load_css_previewer' );

function add_css_style_tag() {
	if ( ! isset( $_GET['nab-css-previewer'] ) ) { // phpcs:ignore
		return;
	}//end if
	$values      = wp_parse_args( array( 0, 0 ), explode( ':', sanitize_text_field( $_GET['nab-css-previewer'] ) ) ); // phpcs:ignore
	$experiment  = absint( $values[0] );
	$experiment  = nab_get_experiment( $experiment );
	$alternative = absint( $values[1] );
	$style       = is_wp_error( $experiment ) ? '' : nab_array_get( $experiment->get_alternatives(), array( $alternative, 'attributes', 'css' ), '' );
	nab_print_html( sprintf( '<style id="nab-css-style" type="text/css">%s</style>', $style ) );
}//end add_css_style_tag()
add_filter( 'wp_head', __NAMESPACE__ . '\add_css_style_tag', 9999 );

function add_css_editor_page() {
	$page = new Nelio_AB_Testing_Css_Editor_Page();
	$page->init();
}//end add_css_editor_page()
add_filter( 'admin_menu', __NAMESPACE__ . '\add_css_editor_page' );

function should_split_testing_be_disabled( $disabled ) {
	if ( isset( $_GET['nab-css-previewer'] ) ) { // phpcs:ignore
		return true;
	}//end if
	return $disabled;
}//end should_split_testing_be_disabled()
add_filter( 'nab_disable_split_testing', __NAMESPACE__ . '\should_split_testing_be_disabled' );
