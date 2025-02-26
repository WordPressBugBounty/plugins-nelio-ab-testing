<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return false;
	}//end if

	return add_query_arg(
		array(
			'page'        => 'nelio-ab-testing-javascript-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}//end get_edit_link()
add_filter( 'nab_nab/javascript_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-javascript-experiment-admin', 'javascript-experiment-admin', true );

	wp_register_style(
		'nab-javascript-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/javascript-experiment-admin.css',
		array( 'wp-admin', 'wp-components' ),
		nelioab()->plugin_version
	);
}//end register_admin_assets()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_assets' );

function register_public_assets() {

	nab_register_script_with_auto_deps( 'nab-javascript-experiment-public', 'javascript-experiment-public', true );

	wp_register_style(
		'nab-javascript-experiment-public',
		nelioab()->plugin_url . '/assets/dist/css/javascript-experiment-public.css',
		array(),
		nelioab()->plugin_version
	);
}//end register_public_assets()
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_public_assets' );

function maybe_load_javascript_previewer() {
	if ( ! isset( $_GET['nab-javascript-previewer'] ) ) { // phpcs:ignore
		return;
	}//end if

	add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore
	wp_enqueue_style( 'nab-javascript-experiment-public' );
	wp_enqueue_script( 'nab-javascript-experiment-public' );

	$values      = sanitize_text_field( $_GET['nab-javascript-previewer'] ); // phpcs:ignore
	$values      = wp_parse_args( array( 0, 0 ), explode( ':', $values ) );
	$experiment  = absint( $values[0] );
	$experiment  = nab_get_experiment( $experiment );
	$alternative = absint( $values[1] );
	$alternative = is_wp_error( $experiment ) ? false : nab_array_get( $experiment->get_alternatives(), array( $alternative ), false );
	$alternative = nab_array_get( $alternative, 'attributes', array() );
	$alternative = encode_alternative( $alternative );
	wp_add_inline_script(
		'nab-javascript-experiment-public',
		sprintf( 'nab.initJavaScriptPreviewer(%s)', wp_json_encode( $alternative ) )
	);
}//end maybe_load_javascript_previewer()
add_filter( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_load_javascript_previewer' );

function add_javascript_editor_page() {
	$page = new Nelio_AB_Testing_JavaScript_Editor_Page();
	$page->init();
}//end add_javascript_editor_page()
add_filter( 'admin_menu', __NAMESPACE__ . '\add_javascript_editor_page' );

function should_split_testing_be_disabled( $disabled ) {
	if ( isset( $_GET['nab-javascript-previewer'] ) ) { // phpcs:ignore
		return true;
	}//end if
	return $disabled;
}//end should_split_testing_be_disabled()
add_filter( 'nab_disable_split_testing', __NAMESPACE__ . '\should_split_testing_be_disabled' );

function set_iframe_loading_status() {
	if ( ! isset( $_GET['nab-javascript-previewer'] ) ) { // phpcs:ignore
		return;
	}//end if

	$mkscript = function ( $enabled ) {
		return function () use ( $enabled ) {
			printf(
				'<script type="text/javascript">window.parent.wp.data.dispatch("nab/data").setPageAttribute("javascript-preview/isLoading",%s)</script>',
				wp_json_encode( $enabled )
			);
		};
	};
	add_action( 'wp_head', $mkscript( true ), 1 );
	add_action( 'wp_footer', $mkscript( false ), 1 );
}//end set_iframe_loading_status()
add_action( 'init', __NAMESPACE__ . '\set_iframe_loading_status' );
