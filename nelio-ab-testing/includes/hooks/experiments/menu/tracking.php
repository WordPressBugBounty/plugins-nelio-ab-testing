<?php
namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to add tracking hooks.
 *
 * @return void
 */
function add_tracking_hooks() {

	$exps_with_loaded_alts = array();

	add_action(
		'nab_nab/menu_load_alternative',
		function ( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			/** @var TMenu_Alternative_Attributes|TMenu_Control_Attributes $alternative   */
			/** @var TMenu_Control_Attributes                                  $control       */
			/** @var int                                                           $experiment_id */

			$tested_menus = array( $control['menuId'] );
			if ( ! empty( $control['testAgainstExistingMenu'] ) ) {
				$experiment = nab_get_experiment( $experiment_id );
				if ( ! is_wp_error( $experiment ) ) {
					$alternatives = $experiment->get_alternatives();
					$tested_menus = array_map( fn( $a ) => absint( $a['attributes']['menuId'] ), $alternatives );
				}
			}

			add_filter(
				'wp_get_nav_menu_items',
				function ( $items, $menu ) use ( $tested_menus, $experiment_id, &$exps_with_loaded_alts ) {
					/** @var list<\WP_Post> $items */
					/** @var \WP_Term       $menu  */

					if ( in_array( $menu->term_id, $tested_menus, true ) ) {
						array_push( $exps_with_loaded_alts, $experiment_id );
					}
					return $items;
				},
				10,
				2
			);
		},
		10,
		3
	);

	add_filter( 'nab_nab/menu_get_page_view_tracking_location', fn() => 'footer' );
	add_filter(
		'nab_nab/menu_should_trigger_footer_page_view',
		function ( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			/** @var bool                                                          $result        */
			/** @var TMenu_Alternative_Attributes|TMenu_Control_Attributes $alternative   */
			/** @var TMenu_Control_Attributes                                  $control       */
			/** @var int                                                           $experiment_id */

			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);
}
add_tracking_hooks();
