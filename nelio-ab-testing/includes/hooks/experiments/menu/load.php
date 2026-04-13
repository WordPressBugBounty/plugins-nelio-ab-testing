<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter( 'nab_nab/menu_experiment_priority', 'nab_return_high_priority' );

/**
 * Callback to get alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TMenu_Control_Attributes,TMenu_Alternative_Attributes>> $loaders        Loaders.
 * @param TMenu_Alternative_Attributes|TMenu_Control_Attributes                                             $alternative    Alternative.
 * @param TMenu_Control_Attributes                                                                          $control        Alternative.
 * @param int                                                                                               $experiment_id  Experiment ID.
 * @param string                                                                                            $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TMenu_Control_Attributes,TMenu_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	if ( empty( $control['testAgainstExistingMenu'] ) ) {
		if ( $alternative['menuId'] === $control['menuId'] ) {
			return $loaders;
		}
	}

	$loaders[] = new Alternative_Menu_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/menu_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
