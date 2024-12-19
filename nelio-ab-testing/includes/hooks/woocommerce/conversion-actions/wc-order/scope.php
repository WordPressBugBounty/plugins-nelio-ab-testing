<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_sanitize_conversion_action_scope',
	function ( $scope, $action ) {
		if ( 'nab/wc-order' !== $action['type'] ) {
			return $scope;
		}//end if

		/**
		 * Filters whether wc-order conversion actions can be tracked on all pages or not.
		 *
		 * @param boolean $enabled whether wc-order conversion actions can be tracked on all pages. Default: `false`.
		 *
		 * @since 6.0.4
		 */
		if ( apply_filters( 'nab_track_woocommerce_orders_on_all_pages', false ) ) {
			return array( 'type' => 'all-pages' );
		}//end if

		return array(
			'type'    => 'php-function',
			'enabled' => function () {
				return function_exists( 'is_checkout' ) && is_checkout();
			},
		);
	},
	10,
	2
);
