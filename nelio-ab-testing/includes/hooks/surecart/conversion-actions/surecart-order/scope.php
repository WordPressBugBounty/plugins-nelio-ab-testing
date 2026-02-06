<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

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
	if ( 'nab/surecart-order' !== $action['type'] ) {
		return $scope;
	}

	/**
		* Filters whether surecart-order conversion actions can be tracked on all pages or not.
		*
		* @param boolean $enabled whether surecart-order conversion actions can be tracked on all pages. Default: `false`.
		*
		* @since 7.2.0
		*/
	if ( apply_filters( 'nab_track_surecart_orders_on_all_pages', false ) ) {
		return array( 'type' => 'all-pages' );
	}

	return array(
		'type'    => 'php-function',
		// OPTIMIZE. Improve this condition in the future if possible.
		'enabled' => '__return_true',
	);
}
add_filter( 'nab_sanitize_conversion_action_scope', __NAMESPACE__ . '\sanitize_conversion_action_scope', 10, 2 );
