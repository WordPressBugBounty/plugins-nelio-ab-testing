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
 * Callback to enable browsing during JavaScript previews.
 *
 * @param bool $enabled Browsing enabled.
 *
 * @return bool
 */
function maybe_enable_browsing_during_preview( $enabled ) {
	if ( ! should_javascript_previewer_be_loaded() ) {
		return $enabled;
	}
	return true;
}
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\maybe_enable_browsing_during_preview' );

/**
 * Callback to add the JavaScript previewer argument in the list of arguments used during JavaScript previews.
 *
 * @param array<string,mixed> $args The arguments that should be added in URL to allow preview browsing.
 *
 * @return array<string,mixed>
 */
function maybe_add_javascript_previewer_param( $args ) {
	if ( ! should_javascript_previewer_be_loaded() ) {
		return $args;
	}
	$args['nab-javascript-previewer'] = true;
	return $args;
}
add_filter( 'nab_preview_browsing_args', __NAMESPACE__ . '\maybe_add_javascript_previewer_param' );

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
	if ( ! should_javascript_previewer_be_loaded() ) {
		return;
	}

	// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
	add_filter( 'show_admin_bar', '__return_false' );
	wp_enqueue_style( 'nab-javascript-experiment-public' );
	wp_enqueue_script( 'nab-javascript-experiment-public' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment = nab_get_experiment( absint( $_GET['experiment'] ?? 0 ) );
	assert( ! is_wp_error( $experiment ) );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alternative = absint( $_GET['alternative'] ?? '' );
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
 * Whether JavaScript previewer is active or not.
 *
 * @return bool
 */
function should_javascript_previewer_be_loaded() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return nab_is_preview() && isset( $_GET['nab-javascript-previewer'] );
}
