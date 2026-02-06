<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function remove_filter;
use function wp_get_nav_menu_items;

add_filter( 'nab_nab/menu_experiment_priority', fn() => 'high' );

/**
 * Callback to add required hooks to load alternative content.
 *
 * @param TMenu_Alternative_Attributes|TMenu_Control_Attributes $alternative    Alternative.
 * @param TMenu_Control_Attributes                              $control        Control.
 * @param int                                                   $experiment_id  Experiment ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id ) {

	$test_against_existing_menu = ! empty( $control['testAgainstExistingMenu'] );
	if ( ! $test_against_existing_menu && $alternative['menuId'] === $control['menuId'] ) {
		return;
	}

	$tested_menus = array( $control['menuId'] );
	if ( $test_against_existing_menu ) {
		$experiment = nab_get_experiment( $experiment_id );
		if ( ! is_wp_error( $experiment ) ) {
			$alternatives = $experiment->get_alternatives();
			$tested_menus = array_map( fn( $a ) => absint( $a['attributes']['menuId'] ), $alternatives );
		}
	}

	$replace_menu = function ( $items, $menu, $args ) use ( &$replace_menu, $alternative, $tested_menus ) {
		/** @var list<\WP_Post>           $items */
		/** @var \WP_Term                 $menu  */
		/** @var array{tax_query?:string} $args  */

		if ( in_array( $menu->term_id, $tested_menus, true ) && is_nav_menu( $alternative['menuId'] ) ) {
			if ( isset( $args['tax_query'] ) ) {
				unset( $args['tax_query'] );
			}
			remove_filter( 'wp_get_nav_menu_items', $replace_menu, 10 );
			$items = wp_get_nav_menu_items( $alternative['menuId'], $args );
			add_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
		}

		return $items;
	};

	add_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
}
add_action( 'nab_nab/menu_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
