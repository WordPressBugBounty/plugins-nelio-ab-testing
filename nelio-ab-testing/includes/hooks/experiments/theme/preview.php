<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to get preview link.
 *
 * @param string|false                                            $preview_link   Preview link.
 * @param TTheme_Alternative_Attributes|TTheme_Control_Attributes $alternative    Alternative.
 * @param TTheme_Control_Attributes                               $control        Control.
 * @param int                                                     $experiment_id  Experiment ID.
 * @param string                                                  $alternative_id Experiment ID.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$theme = wp_get_theme( $alternative['themeId'] ?? '' );
	if ( ! $theme->exists() ) {
		return $preview_link;
	}

	$experiment = nab_get_experiment( $experiment_id );
	assert( ! ( $experiment instanceof \WP_Error ) );
	$scope = $experiment->get_scope();
	$link  = nab_get_preview_url_from_scope( $scope, $alternative_id );
	return ! empty( $link ) ? $link : $preview_link;
}
add_filter( 'nab_nab/theme_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_filter( 'nab_get_nab/theme_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
