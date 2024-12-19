<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function absint;
use function add_filter;
use function add_query_arg;
use function admin_url;
use function sanitize_text_field;
use function wp_enqueue_script;
use function wp_register_style;

function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	return add_query_arg(
		array(
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'widgets.php' )
	);
}//end get_edit_link()
add_filter( 'nab_nab/widget_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

function maybe_add_global_style() {
	if ( ! is_widgets_page() ) {
		return;
	}//end if

	echo '<style type="text/css" id="nab-widget-global-style">';
	if ( uses_widgets_block_editor() ) {
		echo '.wp-block-widget-area { display: none; }';
	}//end if
	echo '</style>';
}//end maybe_add_global_style()
add_action( 'admin_head', __NAMESPACE__ . '\maybe_add_global_style' );

function register_assets() {

	nab_register_script_with_auto_deps( 'nab-widget-experiment-management', 'widget-experiment-management', true );

	wp_register_style(
		'nab-widget-experiment-management',
		nelioab()->plugin_url . '/assets/dist/css/widget-experiment-management.css',
		array( 'wp-admin', 'wp-components', 'nab-components' ),
		nelioab()->plugin_version
	);
}//end register_assets()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_assets' );

function maybe_enqueue_assets_for_control_version() {

	if ( ! is_widgets_page() || is_editing_an_alternative() ) {
		return;
	}//end if

	wp_enqueue_style( 'nab-widget-experiment-management' );
	wp_enqueue_script( 'nab-widget-experiment-management' );

	$functions = uses_widgets_block_editor() ? 'nab.widgets.blocks' : 'nab.widgets.classic';
	wp_add_inline_script( 'nab-widget-experiment-management', "{$functions}.initControlEdition()" );
}//end maybe_enqueue_assets_for_control_version()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_assets_for_control_version' );

function maybe_enqueue_assets_for_alternative() {

	if ( ! is_widgets_page() || ! is_editing_an_alternative() ) {
		return;
	}//end if

	$experiment_id  = absint( nab_array_get( $_GET, 'experiment', 0 ) ); // phpcs:ignore
	$alternative_id = nab_array_get( $_GET, 'alternative', '' ); // phpcs:ignore
	$alternative_id = sanitize_text_field( wp_unslash( $alternative_id ) );
	$experiment     = nab_get_experiment( $experiment_id );

	$settings = array(
		'experiment'  => $experiment_id,
		'alternative' => $alternative_id,
		'sidebars'    => get_sidebar_ids( $experiment_id, $alternative_id ),
		'links'       => array(
			'experimentUrl' => $experiment->get_url(),
		),
	);

	wp_enqueue_style( 'nab-widget-experiment-management' );
	wp_enqueue_script( 'nab-widget-experiment-management' );
	$functions = uses_widgets_block_editor() ? 'nab.widgets.blocks' : 'nab.widgets.classic';
	wp_add_inline_script(
		'nab-widget-experiment-management',
		sprintf(
			"{$functions}.initAlternativeEdition( %s )",
			wp_json_encode( $settings )
		)
	);
}//end maybe_enqueue_assets_for_alternative()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_assets_for_alternative' );

function maybe_die_if_params_are_invalid() {

	if ( ! is_widgets_page() || ! might_be_trying_to_edit_an_alternative() ) {
		return;
	}//end if

	if ( empty( absint( $_GET['experiment'] ) ) ) { // phpcs:ignore
		wp_die( esc_html_x( 'Missing test ID.', 'text', 'nelio-ab-testing' ) );
	}//end if

	if ( empty( $_GET['alternative'] ) ) { // phpcs:ignore
		wp_die( esc_html_x( 'Missing variant ID.', 'text', 'nelio-ab-testing' ) );
	}//end if

	$experiment = nab_get_experiment( absint( $_GET['experiment'] ) ); // phpcs:ignore
	if ( is_wp_error( $experiment ) ) {
		wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}//end if

	if ( 'nab/widget' !== $experiment->get_type() ) {
		wp_die( esc_html_x( 'The test is not a widget test.', 'user', 'nelio-ab-testing' ) );
	}//end if

	$alternative = $experiment->get_alternative( sanitize_text_field( wp_unslash( $_GET['alternative'] ) ) ); // phpcs:ignore
	if ( empty( $alternative ) ) {
		wp_die( esc_html_x( 'You attempted to edit a variant that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}//end if
}//end maybe_die_if_params_are_invalid()
add_filter( 'admin_init', __NAMESPACE__ . '\maybe_die_if_params_are_invalid' );

function is_editing_an_alternative() {
	return isset( $_GET['experiment'] ) && isset( $_GET['alternative'] ); // phpcs:ignore
}//end is_editing_an_alternative()

function might_be_trying_to_edit_an_alternative() {
	return isset( $_GET['experiment'] ) || isset( $_GET['alternative'] ); // phpcs:ignore
}//end might_be_trying_to_edit_an_alternative()

function is_widgets_page() {
	global $pagenow;
	return 'widgets.php' === $pagenow;
}//end is_widgets_page()

function uses_widgets_block_editor() {
	if ( ! function_exists( 'wp_use_widgets_block_editor' ) ) {
		return false;
	}//end if
	return wp_use_widgets_block_editor();
}//end uses_widgets_block_editor()
