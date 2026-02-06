<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Sanitizes conversion action scope.
 *
 * @param TConversion_Action_Scope $scope  Scope.
 * @param TConversion_Action       $action Action.
 *
 * @return TConversion_Action_Scope
 */
function sanitize_conversion_action_scope( $scope, $action ) {
	if ( 'nab/edd-order' !== $action['type'] ) {
		return $scope;
	}

	/**
		* Filters whether edd-order conversion actions can be tracked on all pages or not.
		*
		* @param boolean $enabled whether edd-order conversion actions can be tracked on all pages. Default: `false`.
		*
		* @since 6.0.4
		*/
	if ( apply_filters( 'nab_track_edd_orders_on_all_pages', false ) ) {
		return array( 'type' => 'all-pages' );
	}

	return array(
		'type'    => 'php-function',
		'enabled' => function () {
			return function_exists( 'edd_is_checkout' ) && edd_is_checkout();
		},
	);
}
add_filter( 'nab_sanitize_conversion_action_scope', __NAMESPACE__ . '\sanitize_conversion_action_scope', 10, 2 );
