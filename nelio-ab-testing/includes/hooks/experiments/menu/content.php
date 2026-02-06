<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function wp_create_nav_menu;
use function wp_delete_nav_menu;
use function wp_delete_post;
use function wp_get_nav_menu_items;

/**
 * Callback to create alternative content.
 *
 * @param TMenu_Alternative_Attributes $alternative    Alternative.
 * @param TMenu_Control_Attributes     $control        Control.
 * @param int                          $experiment_id  Experiment ID.
 * @param string                       $alternative_id Alternative ID.
 *
 * @return TMenu_Alternative_Attributes
 */
function create_alternative_content( $alternative, $control, $experiment_id, $alternative_id ) {
	if ( ! empty( $alternative['isExistingMenu'] ) ) {
		return $alternative;
	}

	if ( empty( $control['menuId'] ) ) {
		return $alternative;
	}

	$new_menu_id = wp_create_nav_menu( "Menu $experiment_id $alternative_id" );
	if ( is_wp_error( $new_menu_id ) ) {
		$alternative['menuId'] = 0;
		return $alternative;
	}

	$alternative['menuId'] = $new_menu_id;
	update_term_meta( $alternative['menuId'], '_nab_experiment', $experiment_id );
	duplicate_menu_in_alternative( $control, $alternative );

	return $alternative;
}
add_filter( 'nab_nab/menu_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/menu_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

/**
 * Callback to create alternative content.
 *
 * @param TAttributes              $backup         Backup.
 * @param TMenu_Control_Attributes $control        Control.
 * @param int                      $experiment_id  Experiment ID.
 *
 * @return TMenu_Alternative_Attributes
 */
function backup_control( $backup, $control, $experiment_id ) {
	if ( empty( $control['testAgainstExistingMenu'] ) ) {
		$alternative = array(
			'name'   => '',
			'menuId' => 0,
		);
		return create_alternative_content( $alternative, $control, $experiment_id, 'control' );
	}

	return array(
		'name'           => '',
		'menuId'         => $control['menuId'],
		'isExistingMenu' => true,
	);
}
add_filter( 'nab_nab/menu_backup_control', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

/**
 * Callback to apply the alternative.
 *
 * @param bool                         $applied     Whether the alternative has been applied or not.
 * @param TMenu_Alternative_Attributes $alternative Alternative.
 * @param TMenu_Control_Attributes     $control     Control.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative, $control ) {

	$tested_element = wp_get_nav_menu_items( $control['menuId'] );
	if ( empty( $tested_element ) ) {
		return false;
	}

	$alternative_menu = wp_get_nav_menu_items( $alternative['menuId'] );
	if ( empty( $alternative_menu ) ) {
		$alternative['unableToCreateVariant'] = true;
		return false;
	}

	overwrite_menu( $control['menuId'], $alternative['menuId'] );
	return true;
}
add_filter( 'nab_nab/menu_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

/**
 * Callback to remove alternative.
 *
 * @param TMenu_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function remove_alternative_content( $alternative ) {

	if ( ! empty( $alternative['isExistingMenu'] ) ) {
		return;
	}

	// DEPRECATED. This code is here because we used to create backups using control attributes.
	/** @var array{testAgainstExistingMenu?:bool} $deprecated */
	$deprecated = $alternative;
	if ( ! empty( $deprecated['testAgainstExistingMenu'] ) ) {
		return;
	}

	if ( empty( $alternative['menuId'] ) ) {
		return;
	}

	$dest_prev_items = wp_get_nav_menu_items( $alternative['menuId'] );
	if ( false === $dest_prev_items ) {
		return;
	}

	foreach ( $dest_prev_items as $menu_item ) {
		/** @var \WP_Post $menu_item */
		wp_delete_post( $menu_item->ID, true );
	}

	wp_delete_nav_menu( $alternative['menuId'] );
}
add_action( 'nab_nab/menu_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
