<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function absint;
use function add_filter;
use function add_query_arg;
use function admin_url;
use function get_user_option;
use function is_wp_error;
use function sanitize_text_field;
use function wp_add_inline_script;
use function wp_die;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;
use function wp_safe_redirect;
use function wp_unslash;

function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	return add_query_arg(
		array(
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
			'menu'        => $alternative['menuId'],
		),
		admin_url( 'nav-menus.php' )
	);
}//end get_edit_link()
add_filter( 'nab_nab/menu_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

function register_assets() {

	nab_register_script_with_auto_deps( 'nab-menu-experiment-management', 'menu-experiment-management', true );
}//end register_assets()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_assets' );

function maybe_enqueue_assets_for_alternative() {

	if ( ! is_menu_page() || ! is_editing_an_alternative() ) {
		return;
	}//end if

	$experiment_id  = absint( nab_array_get( $_REQUEST, 'experiment', 0 ) ); // phpcs:ignore
	$alternative_id = sanitize_text_field( wp_unslash( nab_array_get( $_REQUEST, 'alternative', '' ) ) ); // phpcs:ignore
	$experiment     = nab_get_experiment( $experiment_id );

	$settings = array(
		'experiment'  => $experiment_id,
		'alternative' => $alternative_id,
		'links'       => array(
			'experimentUrl' => $experiment->get_url(),
		),
	);

	wp_enqueue_style( 'nab-components' );
	wp_enqueue_script( 'nab-menu-experiment-management' );
	wp_add_inline_script(
		'nab-menu-experiment-management',
		sprintf(
			'nab.initAlternativeEdition( %s )',
			wp_json_encode( $settings )
		)
	);
}//end maybe_enqueue_assets_for_alternative()
add_filter( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_assets_for_alternative' );

function maybe_die_if_params_are_invalid() {

	if ( ! is_menu_page() || ! might_be_trying_to_edit_an_alternative() ) {
		return;
	}//end if

	if ( empty( absint( $_REQUEST['experiment'] ) ) ) { // phpcs:ignore
		wp_die( esc_html_x( 'Missing test ID.', 'text', 'nelio-ab-testing' ) );
	}//end if

	if ( empty( $_REQUEST['alternative'] ) ) { // phpcs:ignore
		wp_die( esc_html_x( 'Missing variant ID.', 'text', 'nelio-ab-testing' ) );
	}//end if

	if ( empty( $_REQUEST['menu'] ) ) { // phpcs:ignore
		wp_die( esc_html_x( 'Missing menu ID.', 'text', 'nelio-ab-testing' ) );
	}//end if

	$experiment = nab_get_experiment( absint( $_REQUEST['experiment'] ) ); // phpcs:ignore
	if ( is_wp_error( $experiment ) ) {
		wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}//end if

	if ( 'nab/menu' !== $experiment->get_type() ) {
		wp_die( esc_html_x( 'The test is not a menu test.', 'user', 'nelio-ab-testing' ) );
	}//end if

	$alternative = $experiment->get_alternative( sanitize_text_field( wp_unslash( $_REQUEST['alternative'] ) ) ); // phpcs:ignore
	if ( empty( $alternative ) ) {
		wp_die( esc_html_x( 'You attempted to edit a variant that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}//end if

	if (
		! isset( $alternative['attributes'] ) ||
		! isset( $alternative['attributes']['menuId'] ) ||
		absint( nab_array_get( $_REQUEST, 'menu', 0 ) ) !== $alternative['attributes']['menuId'] // phpcs:ignore
	) {
		wp_die( esc_html_x( 'Current variant doesn’t have a valid menu.', 'user', 'nelio-ab-testing' ) );
	}//end if
}//end maybe_die_if_params_are_invalid()
add_filter( 'admin_init', __NAMESPACE__ . '\maybe_die_if_params_are_invalid' );

function prevent_recently_edited_menu_from_being_edited() {

	if ( ! is_menu_page() || is_editing_an_alternative() ) {
		return;
	}//end if

	if ( isset( $_GET['menu'] ) ) { // phpcs:ignore
		return;
	}//end if

	if ( 'POST' === nab_array_get( $_SERVER, 'REQUEST_METHOD', '' ) ) {
		return;
	}//end if

	$recently_edited_menu = absint( get_user_option( 'nav_menu_recently_edited' ) );
	if ( empty( $recently_edited_menu ) || ! absint( get_term_meta( $recently_edited_menu, '_nab_experiment', true ) ) ) {
		return;
	}//end if

	$menu  = false;
	$menus = wp_get_nav_menus();
	foreach ( $menus as $candidate ) {
		if ( ! absint( get_term_meta( $candidate->term_id, '_nab_experiment', true ) ) ) {
			$menu = $candidate->term_id;
			break;
		}//end if
	}//end foreach

	if ( empty( $menu ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'action' => 'edit',
					'menu'   => 0,
				),
				admin_url( 'nav-menus.php' )
			)
		);
		exit;
	}//end if

	wp_safe_redirect( add_query_arg( 'menu', $menu, admin_url( 'nav-menus.php' ) ) );
	exit;
}//end prevent_recently_edited_menu_from_being_edited()
add_filter( 'admin_init', __NAMESPACE__ . '\prevent_recently_edited_menu_from_being_edited' );

function is_editing_an_alternative() {
	return isset( $_REQUEST['experiment'] ) && isset( $_REQUEST['alternative'] ) && isset( $_REQUEST['menu'] ); // phpcs:ignore
}//end is_editing_an_alternative()

function might_be_trying_to_edit_an_alternative() {
	return isset( $_REQUEST['experiment'] ) || isset( $_REQUEST['alternative'] ); // phpcs:ignore
}//end might_be_trying_to_edit_an_alternative()

function is_menu_page() {
	global $pagenow;
	return 'nav-menus.php' === $pagenow;
}//end is_menu_page()
