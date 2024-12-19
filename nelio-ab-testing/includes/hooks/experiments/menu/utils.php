<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function wp_delete_post;
use function wp_get_nav_menu_items;
use function wp_update_nav_menu_item;

function duplicate_menu_in_alternative( $control, $alternative ) {

	if ( empty( $control ) || empty( $control['menuId'] ) ) {
		return;
	}//end if

	$src_menu  = $control['menuId'];
	$dest_menu = $alternative['menuId'];

	overwrite_menu( $dest_menu, $src_menu );
}//end duplicate_menu_in_alternative()

function overwrite_menu( $dest_menu, $src_menu ) {

	$source_items = wp_get_nav_menu_items( $src_menu );

	$dest_prev_items = wp_get_nav_menu_items( $dest_menu );
	foreach ( $dest_prev_items as $menu_item ) {
		wp_delete_post( $menu_item->ID, true );
	}//end foreach

	$mappings = array();
	foreach ( $source_items as $menu_item ) {
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

		$new_menu_item_id              = wp_update_nav_menu_item( $dest_menu, 0, $args );
		$mappings[ $menu_item->db_id ] = $new_menu_item_id;

		if ( $menu_item->menu_item_parent ) {
			$args['menu-item-parent-id'] = $mappings[ $menu_item->menu_item_parent ];
			wp_update_nav_menu_item( $dest_menu, $new_menu_item_id, $args );
		}//end if
	}//end foreach
}//end overwrite_menu()
