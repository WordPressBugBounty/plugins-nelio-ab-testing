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

/**
 * Callback to get the edit link.
 *
 * @param string|false                                              $edit_link      Edit link.
 * @param TWidget_Alternative_Attributes|TWidget_Control_Attributes $alternative    Alternative.
 * @param TWidget_Control_Attributes                                $control        Control.
 * @param int                                                       $experiment_id  Experiment ID.
 * @param string                                                    $alternative_id Alternative ID.
 *
 * @return string
 */
function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {
	return add_query_arg(
		array(
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'widgets.php' )
	);
}
add_filter( 'nab_nab/widget_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

/**
 * Callback to add the global style.
 *
 * @return void
 */
function maybe_add_global_style() {
	if ( ! is_widgets_page() ) {
		return;
	}

	echo '<style type="text/css" id="nab-widget-global-style">';
	if ( uses_widgets_block_editor() ) {
		echo '.wp-block-widget-area { display: none; }';
	}
	echo '</style>';
}
add_action( 'admin_head', __NAMESPACE__ . '\maybe_add_global_style' );

/**
 * Callback to register assets.
 *
 * @return void
 */
function register_assets() {

	nab_register_script_with_auto_deps( 'nab-widget-experiment-management', 'widget-experiment-management', true );

	wp_register_style(
		'nab-widget-experiment-management',
		nelioab()->plugin_url . '/assets/dist/css/widget-experiment-management.css',
		array( 'wp-admin', 'wp-components', 'nab-components' ),
		nelioab()->plugin_version
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_assets' );

/**
 * Callback to enqueue assets for the control version.
 *
 * @return void
 */
function maybe_enqueue_assets_for_control_version() {

	if ( ! is_widgets_page() || is_editing_an_alternative() ) {
		return;
	}

	wp_enqueue_style( 'nab-widget-experiment-management' );
	wp_enqueue_script( 'nab-widget-experiment-management' );

	$functions = uses_widgets_block_editor() ? 'nab.widgets.blocks' : 'nab.widgets.classic';
	wp_add_inline_script( 'nab-widget-experiment-management', "{$functions}.initControlEdition()" );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_assets_for_control_version' );

/**
 * Callback to enqueue assets for an alternative.
 *
 * @return void
 */
function maybe_enqueue_assets_for_alternative() {

	if ( ! is_widgets_page() || ! is_editing_an_alternative() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment_id = absint( $_GET['experiment'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alternative_id = sanitize_text_field( wp_unslash( $_GET['alternative'] ?? '' ) );
	$experiment     = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return;
	}

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
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_assets_for_alternative' );

/**
 * Callback to die if params are invalid.
 *
 * @return void
 */
function maybe_die_if_params_are_invalid() {

	if ( ! is_widgets_page() || ! might_be_trying_to_edit_an_alternative() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( absint( $_GET['experiment'] ) ) ) {
		wp_die( esc_html_x( 'Missing test ID.', 'text', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( $_GET['alternative'] ) ) {
		wp_die( esc_html_x( 'Missing variant ID.', 'text', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment = nab_get_experiment( absint( $_GET['experiment'] ) );
	if ( is_wp_error( $experiment ) ) {
		wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}

	if ( 'nab/widget' !== $experiment->get_type() ) {
		wp_die( esc_html_x( 'The test is not a widget test.', 'user', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alternative = sanitize_text_field( wp_unslash( $_GET['alternative'] ) );
	$alternative = $experiment->get_alternative( $alternative );
	if ( empty( $alternative ) ) {
		wp_die( esc_html_x( 'You attempted to edit a variant that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\maybe_die_if_params_are_invalid' );

/**
 * Whether we’re editing an alternative.
 *
 * @return bool
 */
function is_editing_an_alternative() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['experiment'] ) && isset( $_GET['alternative'] );
}

/**
 * Whether we’re trying to edit an alternative.
 *
 * @return bool
 */
function might_be_trying_to_edit_an_alternative() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['experiment'] ) || isset( $_GET['alternative'] );
}

/**
 * Whether it’s the widgets page.
 *
 * @return bool
 */
function is_widgets_page() {
	/** @var string $pagenow */
	global $pagenow;
	return 'widgets.php' === $pagenow;
}

/**
 * Whether it uses the widgets block editor.
 *
 * @return bool
 */
function uses_widgets_block_editor() {
	if ( ! function_exists( 'wp_use_widgets_block_editor' ) ) {
		return false;
	}
	return wp_use_widgets_block_editor();
}
