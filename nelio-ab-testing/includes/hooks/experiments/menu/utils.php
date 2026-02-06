<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function wp_delete_post;
use function wp_get_nav_menu_items;
use function wp_update_nav_menu_item;

/**
 * Duplicates menu in alternative.
 *
 * @param TMenu_Control_Attributes     $control Control.
 * @param TMenu_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function duplicate_menu_in_alternative( $control, $alternative ) {
	$src_menu  = $control['menuId'];
	$dest_menu = $alternative['menuId'];
	overwrite_menu( $dest_menu, $src_menu );
}

/**
 * Overwrites the menu.
 *
 * @param int $dest_menu Destination menu.
 * @param int $src_menu  Source menu.
 *
 * @return void
 */
function overwrite_menu( $dest_menu, $src_menu ) {

	$source_items = wp_get_nav_menu_items( $src_menu );
	if ( false === $source_items ) {
		return;
	}

	$dest_prev_items = wp_get_nav_menu_items( $dest_menu );
	if ( false === $dest_prev_items ) {
		return;
	}

	foreach ( $dest_prev_items as $menu_item ) {
		/** @var TWP_Menu_Item $menu_item */
		wp_delete_post( $menu_item->ID, true );
	}

	$mappings = array();
	foreach ( $source_items as $menu_item ) {
		/** @var TWP_Menu_Item $menu_item */
		$args = array(
			'menu-item-object-id'   => $menu_item->object_id,
			'menu-item-object'      => $menu_item->object,
			'menu-item-position'    => $menu_item->position,
			'menu-item-type'        => $menu_item->type,
			'menu-item-title'       => $menu_item->title,
			'menu-item-url'         => $menu_item->url,
			'menu-item-description' => $menu_item->description,
			'menu-item-attr-title'  => $menu_item->attr_title,
			'menu-item-target'      => $menu_item->target,
			'menu-item-classes'     => implode( ' ', $menu_item->classes ),
			'menu-item-xfn'         => $menu_item->xfn,
			'menu-item-status'      => $menu_item->post_status,
		);

		$new_menu_item_id = wp_update_nav_menu_item( $dest_menu, 0, $args );
		if ( is_wp_error( $new_menu_item_id ) ) {
			continue;
		}
		$mappings[ $menu_item->db_id ] = $new_menu_item_id;

		if ( $menu_item->menu_item_parent ) {
			$args['menu-item-parent-id'] = $mappings[ $menu_item->menu_item_parent ];
			wp_update_nav_menu_item( $dest_menu, $new_menu_item_id, $args );
		}
	}
}
