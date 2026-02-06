<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to get preview link.
 *
 * @param string|false                                                  $preview_link   Preview link.
 * @param THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative    Alternative.
 * @param THeadline_Control_Attributes                                  $control        Control.
 * @param int                                                           $experiment_id  Experiment ID.
 * @param string                                                        $alternative_id Experiment ID.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	assert( ! ( $experiment instanceof \WP_Error ) );
	$scope = $experiment->get_scope();
	return nab_get_preview_url_from_scope( $scope, $alternative_id );
}
add_filter( 'nab_nab/css_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

/**
 * Callback to enable browsing in CSS previews.
 *
 * @param bool   $enabled Enabled.
 * @param string $type    Test type.
 *
 * @return bool
 */
function can_browse_preview( $enabled, $type ) {
	return 'nab/css' === $type ? true : $enabled;
}
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\can_browse_preview', 10, 2 );
