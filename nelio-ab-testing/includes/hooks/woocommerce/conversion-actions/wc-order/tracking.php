<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;
use function nab_track_conversion;
use function wc_get_order;

use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\do_products_match;

/**
 * Adds hooks for tracking WooCommerce’s order completion.
 *
 * @param TWC_Order_Attributes $action Action.
 * @param int                  $experiment_id Experiment ID.
 * @param int                  $goal_index Goal index.
 * @param TGoal                $goal Goal.
 *
 * @return void
 */
function add_hooks_for_tracking( $action, $experiment_id, $goal_index, $goal ) {

	$on_order_processed = function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( empty( $order ) || ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		$experiments = nab_get_experiments_with_page_view_from_request();
		if ( empty( $experiments ) ) {
			return;
		}

		$order->update_meta_data( '_nab_experiments_with_page_view', $experiments );

		$unique_ids = nab_get_unique_views_from_request();
		if ( ! empty( $unique_ids ) ) {
			$order->update_meta_data( '_nab_unique_ids', $unique_ids );
		}

		$segments = nab_get_segments_from_request();
		if ( ! empty( $segments ) ) {
			$order->update_meta_data( '_nab_segments', $segments );
		}

		$ga4_client_id = nab_get_ga4_client_id_from_request();
		if ( ! empty( $ga4_client_id ) ) {
			$order->update_meta_data( '_nab_ga4_client_id', $ga4_client_id );
		}

		$order->save();
	};
	add_action( 'woocommerce_checkout_order_processed', $on_order_processed );
	add_action( 'woocommerce_store_api_checkout_order_processed', $on_order_processed );

	$on_order_status_changed = function ( $order_id, $old_status, $new_status ) use ( $action, $experiment_id, $goal_index, $goal ) {
		/** @var int    $order_id   */
		/** @var string $old_status */
		/** @var string $new_status */

		// If it's a revision or an autosave, do nothing.
		if ( wp_is_post_revision( $order_id ) || wp_is_post_autosave( $order_id ) ) {
			return;
		}

		if ( $old_status === $new_status ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( empty( $order ) || ! ( $order instanceof \WC_Order ) ) {
			return;
		}

		/** @var list<string>|'' */
		$synched_goals = $order->get_meta( '_nab_synched_goals', true );
		$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();
		if ( in_array( "{$experiment_id}:{$goal_index}", $synched_goals, true ) ) {
			return;
		}

		$expected_statuses = get_expected_statuses( $goal );
		if ( ! in_array( $new_status, $expected_statuses, true ) ) {
			return;
		}

		/** @var array<int,int>|'' */
		$experiments = $order->get_meta( '_nab_experiments_with_page_view', true );
		if ( empty( $experiments ) || ! isset( $experiments[ $experiment_id ] ) ) {
			return;
		}

		$product_ids = get_product_ids( $order );
		if ( ! do_products_match( $action['value'], $product_ids ) ) {
			return;
		}

		$value       = get_conversion_value( $order, $goal );
		$alternative = $experiments[ $experiment_id ];
		$options     = array( 'value' => $value );

		/** @var array<int,string>|'' */
		$unique_ids = $order->get_meta( '_nab_unique_ids', true );
		$unique_ids = ! empty( $unique_ids ) ? $unique_ids : array();
		if ( ! empty( $unique_ids[ $experiment_id ] ) ) {
			$options['unique_id'] = $unique_ids[ $experiment_id ];
		}

		/** @var array<int,list<int>>|'' */
		$segments = $order->get_meta( '_nab_segments', true );
		$segments = ! empty( $segments ) ? $segments : array();
		if ( isset( $segments[ $experiment_id ] ) ) {
			$options['segments'] = $segments[ $experiment_id ];
		}

		/** @var string|null */
		$ga4_client_id = $order->get_meta( '_nab_ga4_client_id', true );
		if ( ! empty( $ga4_client_id ) ) {
			$options['ga4_client_id'] = $ga4_client_id;
		}

		nab_track_conversion( $experiment_id, $goal_index, $alternative, $options );
		array_push( $synched_goals, "{$experiment_id}:{$goal_index}" );
		$order->update_meta_data( '_nab_synched_goals', $synched_goals );
		$order->save();
	};
	add_action( 'woocommerce_order_status_changed', $on_order_status_changed, 10, 3 );
}
add_action( 'nab_nab/wc-order_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 4 );

/**
 * Retuns conversion value.
 *
 * @param \WC_Order $order Order.
 * @param TGoal     $goal  Goal.
 *
 * @return number
 */
function get_conversion_value( $order, $goal ) {
	$attrs = $goal['attributes'];
	if ( empty( $attrs['useOrderRevenue'] ) ) {
		return 0;
	}

	/**
	 * Filters which products in an order contribute to the conversion revenue.
	 *
	 * In WooCommerce order conversion actions, when there’s a new order
	 * containing tracked produts, this filter specifies whether it should
	 * track the order total or only the value of the tracked products.
	 *
	 * @param boolean   $track_order_total Default: `false`.
	 * @param \WC_Order $order             The order.
	 *
	 * @since 6.4.0
	 */
	if ( apply_filters( 'nab_track_wc_order_total', false, $order ) ) {
		return filter_order_value( 0 + $order->get_total(), $order );
	}

	$actions         = get_wc_order_actions( $goal );
	$is_tracked_item = function ( $item ) use ( &$actions ) {
		/** @var \WC_Order_Item_Product $item */

		$product_id = absint( $item->get_product_id() );
		return nab_some(
			function ( $action ) use ( $product_id ) {
				return do_products_match( $action['value'], $product_id );
			},
			$actions
		);
	};

	$items = $order->get_items();
	/** @var array<\WC_Order_Item_Product> */
	$items = array_filter( $items, fn( $i ) => $i instanceof \WC_Order_Item_Product );
	$items = array_filter( $items, $is_tracked_item );
	$items = array_values( $items );

	$value = array_reduce(
		$items,
		function ( $carry, $item ) {
			return $carry + (float) $item->get_total();
		},
		0
	);
	return filter_order_value( $value, $order );
}

/**
 * Filters order value.
 *
 * @param number    $value Value.
 * @param \WC_Order $order Order.
 *
 * @return number
 */
function filter_order_value( $value, $order ) {
	/**
	 * Filters the value of an WC order.
	 *
	 * @param number    $value the order value (be it the full order or just the relevant items in it).
	 * @param \WC_Order $order the order.
	 *
	 * @since 6.4.0
	 */
	$value = apply_filters( 'nab_wc_order_value', $value, $order );
	return 0 + $value;
}

/**
 * Returns product IDs included in order.
 *
 * @param \WC_Order $order Order.
 *
 * @return list<int>
 */
function get_product_ids( $order ) {
	$order_items = $order->get_items();
	$product_ids = array_map(
		fn( $item ) => absint( $item instanceof \WC_Order_Item_Product ? $item->get_product_id() : 0 ),
		$order_items
	);
	return array_values( array_unique( array_filter( $product_ids ) ) );
}

/**
 * Returns WC order actions.
 *
 * @param TGoal $goal Goal.
 *
 * @return list<TWC_Order_Attributes>
 */
function get_wc_order_actions( $goal ) {
	$actions = $goal['conversionActions'];
	$actions = array_filter( $actions, fn( $a ) => 'nab/wc-order' === $a['type'] );
	$actions = array_map( fn( $a ) => $a['attributes'], $actions );
	$actions = array_map( fn( $a ) => modernize( $a ), $actions );
	return array_values( $actions );
}

/**
 * Returns list of expected statuses.
 *
 * @param TGoal $goal Goal.
 *
 * @return list<string>
 */
function get_expected_statuses( $goal ) {
	$attrs  = $goal['attributes'];
	$status = isset( $attrs['orderStatusForConversion'] ) ? $attrs['orderStatusForConversion'] : 'wc-completed';
	$status = str_replace( 'wc-', '', $status );

	/**
	 * Returns the statuses that might trigger a conversion when there’s a WooCommerce order.
	 * Don’t include the `wc-` prefix in status names.
	 *
	 * @param list<string>|string $statuses the status (or statuses) that might trigger a conversion.
	 *
	 * @since 5.0.0
	 */
	$expected_statuses = apply_filters( 'nab_order_status_for_conversions', $status );
	if ( ! is_array( $expected_statuses ) ) {
		$expected_statuses = array( $expected_statuses );
	}

	return $expected_statuses;
}
