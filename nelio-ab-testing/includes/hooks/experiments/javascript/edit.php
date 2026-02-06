<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                                      $edit_link      Edit link.
 * @param TJavaScript_Alternative_Attributes|TJavaScript_Control_Attributes $alternative    Alternative.
 * @param TJavaScript_Control_Attributes                                    $control        Control.
 * @param int                                                               $experiment_id  Experiment ID.
 * @param string                                                            $alternative_id Alternative ID.
 *
 * @return string|false
 */
function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return false;
	}

	return add_query_arg(
		array(
			'page'        => 'nelio-ab-testing-javascript-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}
add_filter( 'nab_nab/javascript_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

/**
 * Callback to register admin assets.
 *
 * @return void
 */
function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-javascript-experiment-admin', 'javascript-experiment-admin', true );

	wp_register_style(
		'nab-javascript-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/javascript-experiment-admin.css',
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

	nab_register_script_with_auto_deps( 'nab-javascript-experiment-public', 'javascript-experiment-public', true );

	wp_register_style(
		'nab-javascript-experiment-public',
		nelioab()->plugin_url . '/assets/dist/css/javascript-experiment-public.css',
		array(),
		nelioab()->plugin_version
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_public_assets' );

/**
 * Callback to enqueue required scripts and styles to preview JavaScript tests.
 *
 * @return void
 */
function maybe_load_javascript_previewer() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-javascript-previewer'] ) ) {
		return;
	}

	// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
	add_filter( 'show_admin_bar', '__return_false' );
	wp_enqueue_style( 'nab-javascript-experiment-public' );
	wp_enqueue_script( 'nab-javascript-experiment-public' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$values = sanitize_text_field( wp_unslash( $_GET['nab-javascript-previewer'] ) );
	$values = wp_parse_args( array( 0, 0 ), explode( ':', $values ) );

	$experiment = absint( $values[0] );
	$experiment = nab_get_experiment( $experiment );
	if ( is_wp_error( $experiment ) ) {
		return;
	}

	$alternative = absint( $values[1] );
	$alternative = $experiment->get_alternatives()[ $alternative ] ?? array();
	$alternative = $alternative['attributes'] ?? array();
	$alternative = array(
		'name' => is_string( $alternative['name'] ?? '' ) ? ( $alternative['name'] ?? '' ) : '',
		'code' => is_string( $alternative['code'] ?? '' ) ? ( $alternative['code'] ?? '' ) : '',
	);
	$alternative = encode_alternative( $alternative );

	/** @var \WP $wp */
	global $wp;
	$url     = trailingslashit( home_url( $wp->request ) );
	$context = array( 'url' => trailingslashit( home_url( $wp->request ) ) );
	$enabled = nab_is_experiment_relevant( $context, $experiment );

	/**
	 * Filters whether the test is in scope or not.
	 *
	 * @param boolean                      $enabled    whether the test is in scope or not.
	 * @param string                       $url        current URL.
	 * @param \Nelio_AB_Testing_Experiment $experiment current test.
	 */
	$enabled = apply_filters( 'nab_javascript_previewer_is_url_in_scope', $enabled, $url, $experiment );

	wp_add_inline_script(
		'nab-javascript-experiment-public',
		sprintf( 'nab.initJavaScriptPreviewer(%1$s, %2$s)', wp_json_encode( $alternative ), wp_json_encode( $enabled ) )
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_load_javascript_previewer' );

/**
 * Callback to register the JavaScript Editor page in the Dashboard.
 *
 * @return void
 */
function add_javascript_editor_page() {
	$page = new Nelio_AB_Testing_JavaScript_Editor_Page();
	$page->init();
}
add_action( 'admin_menu', __NAMESPACE__ . '\add_javascript_editor_page' );

/**
 * Callback to disable split testing while previewing JavaScript tests.
 *
 * @param bool $disabled Disabled.
 *
 * @return bool
 */
function should_split_testing_be_disabled( $disabled ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['nab-javascript-previewer'] ) ) {
		return true;
	}
	return $disabled;
}
add_filter( 'nab_disable_split_testing', __NAMESPACE__ . '\should_split_testing_be_disabled' );
