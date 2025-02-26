<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_filter;
add_filter(
	'nab_sanitize_conversion_action_scope',
	function ( $scope, $action ) {
		if ( 'nab/surecart-order' !== $action['type'] ) {
			return $scope;
		}//end if

		/**
		 * Filters whether surecart-order conversion actions can be tracked on all pages or not.
		 *
		 * @param boolean $enabled whether surecart-order conversion actions can be tracked on all pages. Default: `false`.
		 *
		 * @since 7.2.0
		 */
		if ( apply_filters( 'nab_track_surecart_orders_on_all_pages', false ) ) {
			return array( 'type' => 'all-pages' );
		}//end if

		return array(
			'type'    => 'php-function',
			'enabled' => '__return_true', // TODO: Improve this condition in the future if possible.
		);
	},
	10,
	2
);
