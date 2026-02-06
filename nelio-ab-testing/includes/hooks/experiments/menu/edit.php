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

/**
 * Callback to get the edit link.
 *
 * @param string|false                                          $edit_link      Edit link.
 * @param TMenu_Alternative_Attributes|TMenu_Control_Attributes $alternative    Alternative.
 * @param TMenu_Control_Attributes                              $control        Control.
 * @param int                                                   $experiment_id  Experiment ID.
 * @param string                                                $alternative_id Alternative ID.
 *
 * @return string
 */
function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {
	return add_query_arg(
		array(
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
			'menu'        => $alternative['menuId'],
		),
		admin_url( 'nav-menus.php' )
	);
}
add_filter( 'nab_nab/menu_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

/**
 * Callback to register assets.
 *
 * @return void
 */
function register_assets() {
	nab_register_script_with_auto_deps( 'nab-menu-experiment-management', 'menu-experiment-management', true );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_assets' );

/**
 * Callback to enqueue required assest for editing alternative.
 *
 * @return void
 */
function maybe_enqueue_assets_for_alternative() {

	if ( ! is_menu_page() || ! is_editing_an_alternative() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment_id = absint( $_REQUEST['experiment'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alternative_id = sanitize_text_field( wp_unslash( $_REQUEST['alternative'] ?? '' ) );
	$experiment     = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return;
	}

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
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_assets_for_alternative' );

/**
 * Callback to die if parameters are invalid.
 *
 * @return void
 */
function maybe_die_if_params_are_invalid() {

	if ( ! is_menu_page() || ! might_be_trying_to_edit_an_alternative() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( absint( $_REQUEST['experiment'] ) ) ) {
		wp_die( esc_html_x( 'Missing test ID.', 'text', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( $_REQUEST['alternative'] ) ) {
		wp_die( esc_html_x( 'Missing variant ID.', 'text', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( $_REQUEST['menu'] ) ) {
		wp_die( esc_html_x( 'Missing menu ID.', 'text', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment = nab_get_experiment( absint( $_REQUEST['experiment'] ) );
	if ( is_wp_error( $experiment ) ) {
		wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}

	if ( 'nab/menu' !== $experiment->get_type() ) {
		wp_die( esc_html_x( 'The test is not a menu test.', 'user', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alternative = $experiment->get_alternative( sanitize_text_field( wp_unslash( $_REQUEST['alternative'] ) ) );
	if ( empty( $alternative ) ) {
		wp_die( esc_html_x( 'You attempted to edit a variant that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$menu = absint( $_REQUEST['menu'] );
	if ( ! isset( $alternative['attributes']['menuId'] ) || $menu !== $alternative['attributes']['menuId'] ) {
		wp_die( esc_html_x( 'Current variant doesn’t have a valid menu.', 'user', 'nelio-ab-testing' ) );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\maybe_die_if_params_are_invalid' );

/**
 * Callback to prevent recently edited menu from being edited again.
 *
 * @return void
 */
function prevent_recently_edited_menu_from_being_edited() {

	if ( ! is_menu_page() || is_editing_an_alternative() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['menu'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		return;
	}

	$recently_edited_menu = absint( get_user_option( 'nav_menu_recently_edited' ) );
	if ( empty( $recently_edited_menu ) || ! absint( get_term_meta( $recently_edited_menu, '_nab_experiment', true ) ) ) {
		return;
	}

	$menu  = false;
	$menus = wp_get_nav_menus();
	foreach ( $menus as $candidate ) {
		if ( ! absint( get_term_meta( $candidate->term_id, '_nab_experiment', true ) ) ) {
			$menu = $candidate->term_id;
			break;
		}
	}

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
	}

	wp_safe_redirect( add_query_arg( 'menu', $menu, admin_url( 'nav-menus.php' ) ) );
	exit;
}
add_action( 'admin_init', __NAMESPACE__ . '\prevent_recently_edited_menu_from_being_edited' );

/**
 * Whether we’re editing an alternative menu or not.
 *
 * @return bool
 */
function is_editing_an_alternative() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_REQUEST['experiment'] ) && isset( $_REQUEST['alternative'] ) && isset( $_REQUEST['menu'] );
}

/**
 * Whether we’re trying to edit an alternative menu or not.
 *
 * @return bool
 */
function might_be_trying_to_edit_an_alternative() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_REQUEST['experiment'] ) || isset( $_REQUEST['alternative'] );
}

/**
 * Whether the current page is the nab-menus.php page.
 *
 * @return bool
 */
function is_menu_page() {
	/** @var string $pagenow */
	global $pagenow;
	return 'nav-menus.php' === $pagenow;
}
