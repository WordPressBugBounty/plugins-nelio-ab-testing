<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function remove_filter;
use function wp_get_nav_menu_items;

add_action( 'nab_nab/menu_experiment_priority', fn() => 'high' );

function load_alternative( $alternative, $control, $experiment_id ) {

	$test_against_existing_menu = ! empty( $control['testAgainstExistingMenu'] );
	if ( ! $test_against_existing_menu && $alternative['menuId'] === $control['menuId'] ) {
		return;
	}//end if

	$tested_menus = array( $control['menuId'] );
	if ( $test_against_existing_menu ) {
		$experiment = nab_get_experiment( $experiment_id );
		if ( ! is_wp_error( $experiment ) ) {
			$alternatives = $experiment->get_alternatives();
			$tested_menus = array_map( fn( $a ) => absint( $a['attributes']['menuId'] ), $alternatives );
		}//end if
	}//end if

	$replace_menu = function ( $items, $menu, $args ) use ( &$replace_menu, $alternative, $tested_menus ) {

		if ( in_array( $menu->term_id, $tested_menus, true ) && is_nav_menu( $alternative['menuId'] ) ) {
			if ( isset( $args['tax_query'] ) ) {
				unset( $args['tax_query'] );
			}//end if
			remove_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
			$items = wp_get_nav_menu_items( $alternative['menuId'], $args );
			add_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
		}//end if

		return $items;
	};

	add_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
}//end load_alternative()
add_action( 'nab_nab/menu_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
