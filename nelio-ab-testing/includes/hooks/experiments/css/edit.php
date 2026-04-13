<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                        $edit_link      Edit link.
 * @param TCss_Alternative_Attributes|TCss_Control_Attributes $alternative    Alternative.
 * @param TCss_Control_Attributes                             $control        Control.
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
			'page'        => 'nelio-ab-testing-css-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}
add_filter( 'nab_nab/css_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

/**
 * Callback to register admin assets.
 *
 * @return void
 */
function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-css-experiment-admin', 'css-experiment-admin', true );

	wp_register_style(
		'nab-css-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/css-experiment-admin.css',
		array( 'wp-admin', 'wp-components', 'wp-editor', 'wp-block-editor', 'wp-reset-editor-styles', 'wp-block-library' ),
		nelioab()->plugin_version
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_assets' );

/**
 * Callback to enable browsing during CSS previews.
 *
 * @param bool $enabled Browsing enabled.
 *
 * @return bool
 */
function maybe_enable_browsing_during_preview( $enabled ) {
	if ( ! should_css_previewer_be_loaded() ) {
		return $enabled;
	}
	return true;
}
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\maybe_enable_browsing_during_preview' );

/**
 * Callback to add the CSS previewer argument in the list of arguments used during CSS previews.
 *
 * @param array<string,mixed> $args The arguments that should be added in URL to allow preview browsing.
 *
 * @return array<string,mixed>
 */
function maybe_add_css_previewer_param( $args ) {
	if ( ! should_css_previewer_be_loaded() ) {
		return $args;
	}
	$args['nab-css-previewer'] = true;
	return $args;
}
add_filter( 'nab_preview_browsing_args', __NAMESPACE__ . '\maybe_add_css_previewer_param' );

/**
 * Callback to register public assets.
 *
 * @return void
 */
function register_public_assets() {
	nab_register_script_with_auto_deps( 'nab-css-experiment-public', 'css-experiment-public', true );

	wp_register_style(
		'nab-css-experiment-public',
		nelioab()->plugin_url . '/assets/dist/css/css-experiment-public.css',
		array(),
		nelioab()->plugin_version
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_public_assets' );

/**
 * Callback to enqueue required scripts and styles to preview CSS tests.
 *
 * @return void
 */
function maybe_load_css_previewer() {
	if ( ! should_css_previewer_be_loaded() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment = nab_get_experiment( absint( $_GET['experiment'] ?? 0 ) );
	assert( ! is_wp_error( $experiment ) );

	// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
	add_filter( 'show_admin_bar', '__return_false' );
	wp_enqueue_style( 'nab-css-experiment-public' );
	wp_enqueue_script( 'nab-css-experiment-public' );

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
	$enabled = apply_filters( 'nab_css_previewer_is_url_in_scope', $enabled, $url, $experiment );

	wp_add_inline_script( 'nab-css-experiment-public', sprintf( 'nab.initCssPreviewer( %s )', wp_json_encode( $enabled ) ) );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_load_css_previewer' );

/**
 * Callback to add a CSS style tag in head for previewing purposes.
 *
 * @return void
 */
function maybe_print_css_style_tag() {
	if ( ! should_css_previewer_be_loaded() ) {
		return;
	}
	echo '<style id="nab-css-style" type="text/css"></style>';
}
add_action( 'wp_head', __NAMESPACE__ . '\maybe_print_css_style_tag', 9999 );
add_action( 'nab_external_page_script_print_assets', __NAMESPACE__ . '\maybe_print_css_style_tag', 9999 );

/**
 * Callback to register the CSS Editor page in the Dashboard.
 *
 * @return void
 */
function add_css_editor_page() {
	$page = new Nelio_AB_Testing_Css_Editor_Page();
	$page->init();
}
add_action( 'admin_menu', __NAMESPACE__ . '\add_css_editor_page' );

/**
 * Whether CSS previewer is active or not.
 *
 * @return bool
 */
function should_css_previewer_be_loaded() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return nab_is_preview() && isset( $_GET['nab-css-previewer'] ) && isset( $_GET['nab-css-selector-finder'] );
}
