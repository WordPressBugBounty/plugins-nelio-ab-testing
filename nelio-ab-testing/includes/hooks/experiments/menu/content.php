<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function wp_create_nav_menu;
use function wp_delete_nav_menu;
use function wp_delete_post;
use function wp_get_nav_menu_items;

function create_alternative_content( $alternative, $control, $experiment_id, $alternative_id ) {
	if ( ! empty( $alternative['isExistingMenu'] ) ) {
		return $alternative;
	}//end if

	if ( empty( $control['menuId'] ) ) {
		return $alternative;
	}//end if

	$alternative['menuId'] = wp_create_nav_menu( "Menu $experiment_id $alternative_id" );
	update_term_meta( $alternative['menuId'], '_nab_experiment', $experiment_id );
	duplicate_menu_in_alternative( $control, $alternative );

	return $alternative;
}//end create_alternative_content()
add_filter( 'nab_nab/menu_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/menu_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

function backup_control( $alternative, $control, $experiment_id ) {
	return empty( $alternative['testAgainstExistingMenu'] )
		? create_alternative_content( $alternative, $control, $experiment_id, 'control' )
		: $alternative;
}//end backup_control()
add_filter( 'nab_nab/menu_backup_control', __NAMESPACE__ . '\create_alternative_content', 10, 4 );
add_action( 'nab_remove_nab/menu_control_backup', __NAMESPACE__ . '\remove_alternative_content' );

function apply_alternative( $applied, $alternative, $control ) {

	$tested_element = wp_get_nav_menu_items( $control['menuId'] );
	if ( empty( $tested_element ) || is_wp_error( $tested_element ) ) {
		return false;
	}//end if

	$alternative_menu = wp_get_nav_menu_items( $alternative['menuId'] );
	if ( empty( $alternative_menu ) || is_wp_error( $alternative_menu ) ) {
		$alternative['unableToCreateVariant'] = true;
		return false;
	}//end if

	overwrite_menu( $control['menuId'], $alternative['menuId'] );
	return true;
}//end apply_alternative()
add_filter( 'nab_nab/menu_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

function remove_alternative_content( $alternative ) {

	if ( ! empty( $alternative['isExistingMenu'] ) ) {
		return;
	}//end if

	if ( ! empty( $alternative['testAgainstExistingMenu'] ) ) {
		return;
	}//end if

	if ( empty( $alternative['menuId'] ) ) {
		return;
	}//end if

	$dest_prev_items = wp_get_nav_menu_items( $alternative['menuId'] );
	foreach ( $dest_prev_items as $menu_item ) {
		wp_delete_post( $menu_item->ID, true );
	}//end foreach

	wp_delete_nav_menu( $alternative['menuId'], true );
}//end remove_alternative_content()
add_action( 'nab_nab/menu_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
