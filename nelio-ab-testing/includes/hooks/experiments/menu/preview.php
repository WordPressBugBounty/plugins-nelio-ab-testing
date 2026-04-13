<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function nab_get_experiment;

/**
 * Callback to get preview link.
 *
 * @param string|false                                          $preview_link   Preview link.
 * @param TMenu_Alternative_Attributes|TMenu_Control_Attributes $alternative    Alternative.
 * @param TMenu_Control_Attributes                              $control        Control.
 * @param int                                                   $experiment_id  Experiment ID.
 * @param string                                                $alternative_id Experiment ID.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$tested_element = wp_get_nav_menu_items( $control['menuId'] );
	if ( empty( $tested_element ) ) {
		return $preview_link;
	}

	$experiment = nab_get_experiment( $experiment_id );
	assert( ! ( $experiment instanceof \WP_Error ) );
	$scope = $experiment->get_scope();
	$link  = nab_get_preview_url_from_scope( $scope, $alternative_id );
	return ! empty( $link ) ? $link : $preview_link;
}
add_filter( 'nab_nab/menu_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_filter( 'nab_get_nab/menu_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );

/**
 * Callback to enable browsing in menu previews.
 *
 * @param bool   $enabled Enabled.
 * @param string $type    Test type.
 *
 * @return bool
 */
function can_browse_preview( $enabled, $type ) {
	return 'nab/menu' === $type ? true : $enabled;
}
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\can_browse_preview', 10, 2 );
