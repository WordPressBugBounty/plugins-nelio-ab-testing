<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function nab_get_experiment;

use Nelio_AB_Testing_Experiment_Helper;

function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$tested_element = wp_get_nav_menu_items( $control['menuId'] );
	if ( empty( $tested_element ) || is_wp_error( $tested_element ) ) {
		return false;
	}//end if

	$experiment = nab_get_experiment( $experiment_id );
	$scope      = $experiment->get_scope();
	return Nelio_AB_Testing_Experiment_Helper::instance()->get_preview_url_from_scope( $scope, $experiment_id, $alternative_id );

}//end get_preview_link()
add_filter( 'nab_nab/menu_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_action( 'nab_nab/menu_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

function can_browse_preview( $enabled, $type ) {
	return 'nab/menu' === $type ? true : $enabled;
}//end can_browse_preview()
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\can_browse_preview', 10, 2 );
