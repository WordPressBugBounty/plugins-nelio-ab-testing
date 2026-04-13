<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function wp_get_theme;

add_filter( 'nab_nab/theme_experiment_priority', 'nab_return_high_priority' );

/**
 * Callback to add hooks to load alternative.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TTheme_Control_Attributes,TTheme_Alternative_Attributes>> $loaders        Loaders.
 * @param TTheme_Alternative_Attributes|TTheme_Control_Attributes                                             $alternative    Alternative.
 * @param TTheme_Control_Attributes                                                                           $control        Alternative.
 * @param int                                                                                                 $experiment_id  Experiment ID.
 * @param string                                                                                              $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TTheme_Control_Attributes,TTheme_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	if ( 'control' === $alternative_id ) {
		return $loaders;
	}

	$theme = wp_get_theme( $alternative['themeId'] ?? '' );
	if ( ! $theme->exists() ) {
		return $loaders;
	}

	$loader = new Alternative_Theme_Loader( $alternative, $control, $experiment_id, $alternative_id );
	$loader->set_theme( $theme );
	$loaders[] = $loader;
	return $loaders;
}
add_filter( 'nab_get_nab/theme_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
