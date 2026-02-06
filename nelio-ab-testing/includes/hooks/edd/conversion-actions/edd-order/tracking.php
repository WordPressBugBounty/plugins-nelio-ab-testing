<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;
use function nab_track_conversion;
use function edd_get_order;
use function edd_get_order_meta;
use function edd_update_order_meta;

use function Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\do_downloads_match;

/**
 * Adds hooks for tracking EDD’s order completion.
 *
 * @param TEdd_Order_Attributes $action Action.
 * @param int                   $experiment_id Experiment ID.
 * @param int                   $goal_index Goal index.
 * @param TGoal                 $goal Goal.
 *
 * @return void
 */
function add_hooks_for_tracking( $action, $experiment_id, $goal_index, $goal ) {

	add_action(
		'edd_built_order',
		function ( $order_id ): void {
			/** @var int $order_id */

			$experiments = nab_get_experiments_with_page_view_from_request();
			if ( empty( $experiments ) ) {
				return;
			}
			edd_update_order_meta( $order_id, '_nab_experiments_with_page_view', $experiments );

			$unique_ids = nab_get_unique_views_from_request();
			if ( ! empty( $unique_ids ) ) {
				edd_update_order_meta( $order_id, '_nab_unique_ids', $unique_ids );
			}

			$segments = nab_get_segments_from_request();
			if ( ! empty( $segments ) ) {
				edd_update_order_meta( $order_id, '_nab_segments', $segments );
			}

			$ga4_client_id = nab_get_ga4_client_id_from_request();
			if ( ! empty( $ga4_client_id ) ) {
				edd_update_order_meta( $order_id, '_nab_ga4_client_id', $ga4_client_id );
			}
		}
	);

	add_action(
		'edd_update_payment_status',
		function ( $order_id, $new_status, $old_status ) use ( $action, $experiment_id, $goal_index, $goal ): void {
			/** @var int    $order_id   */
			/** @var string $new_status */
			/** @var string $old_status */

			if ( $old_status === $new_status ) {
				return;
			}

			if ( ! function_exists( 'edd_get_order_meta' ) ) {
				return;
			}

			/** @var list<string>|null */
			$synched_goals = edd_get_order_meta( $order_id, '_nab_synched_goals', true );
			$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();
			if ( in_array( "{$experiment_id}:{$goal_index}", $synched_goals, true ) ) {
				return;
			}

			$expected_statuses = get_expected_statuses( $goal );
			if ( ! in_array( $new_status, $expected_statuses, true ) ) {
				return;
			}

			/** @var array<int,int>|null */
			$experiments = edd_get_order_meta( $order_id, '_nab_experiments_with_page_view', true );
			if ( empty( $experiments ) || ! isset( $experiments[ $experiment_id ] ) ) {
				return;
			}

			if ( ! function_exists( 'edd_get_order' ) ) {
				return;
			}

			$order = edd_get_order( $order_id );
			if ( empty( $order ) ) {
				return;
			}

			$download_ids = get_download_ids( $order );
			if ( ! do_downloads_match( $action['value'], $download_ids ) ) {
				return;
			}

			$value       = get_conversion_value( $order, $goal );
			$alternative = $experiments[ $experiment_id ];
			$options     = array( 'value' => $value );

			/** @var array<int,string>|null */
			$unique_ids = edd_get_order_meta( $order_id, '_nab_unique_ids', true );
			if ( isset( $unique_ids[ $experiment_id ] ) ) {
				$options['unique_id'] = $unique_ids[ $experiment_id ];
			}

			/** @var array<int,list<int>>|null */
			$segments = edd_get_order_meta( $order_id, '_nab_segments', true );
			if ( isset( $segments[ $experiment_id ] ) ) {
				$options['segments'] = $segments[ $experiment_id ];
			}

			/** @var string|null */
			$ga4_client_id = edd_get_order_meta( $order_id, '_nab_ga4_client_id', true );
			if ( ! empty( $ga4_client_id ) ) {
				$options['ga4_client_id'] = $ga4_client_id;
			}

			nab_track_conversion( $experiment_id, $goal_index, $alternative, $options );
			array_push( $synched_goals, "{$experiment_id}:{$goal_index}" );
			edd_update_order_meta( $order_id, '_nab_synched_goals', $synched_goals );
		},
		10,
		3
	);
}
add_action( 'nab_nab/edd-order_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 4 );

/**
 * Retuns conversion value.
 *
 * @param \EDD\Orders\Order $order Order.
 * @param TGoal             $goal  Goal.
 *
 * @return float
 */
function get_conversion_value( $order, $goal ) {
	$attrs = $goal['attributes'];
	if ( empty( $attrs['useOrderRevenue'] ) ) {
		return 0;
	}

	/**
	 * Filters which products in an order contribute to the conversion revenue.
	 *
	 * In Easy Digital Downloads order conversion actions, when there’s a new
	 * order containing tracked downloads, this filter specifies whether it
	 * should track the order total or only the value of the tracked downloads.
	 *
	 * @param boolean           $track_order_total Default: `false`.
	 * @param \EDD\Orders\Order $order             The order.
	 *
	 * @since 6.4.0
	 */
	if ( apply_filters( 'nab_track_edd_order_total', false, $order ) ) {
		return filter_order_value( 0 + (float) $order->total, $order );
	}

	$actions         = get_edd_order_actions( $goal );
	$is_tracked_item = function ( $item ) use ( &$actions ) {
		/** @var \EDD\Orders\Order_Item $item */
		$download_id = absint( $item->product_id );
		return nab_some(
			function ( $action ) use ( $download_id ) {
				return do_downloads_match( $action['value'], $download_id );
			},
			$actions
		);
	};

	$items = array_filter( $order->get_items(), $is_tracked_item );
	$items = array_values( $items );

	$value = array_reduce(
		$items,
		function ( $carry, $item ) {
			return $carry + $item->total;
		},
		0
	);
	return filter_order_value( $value, $order );
}

/**
 * Filters order value.
 *
 * @param number            $value Actual value.
 * @param \EDD\Orders\Order $order Order.
 *
 * @return number
 */
function filter_order_value( $value, $order ) {
	/**
	 * Filters the value of an EDD order.
	 *
	 * @param number            $value Order value (be it the full order or just the relevant items in it).
	 * @param \EDD\Orders\Order $order Order.
	 *
	 * @since 6.4.0
	 */
	$value = apply_filters( 'nab_edd_order_value', $value, $order );
	return 0 + $value;
}

/**
 * Returns list of downloaded item IDs.
 *
 * @param \EDD\Orders\Order $order .
 *
 * @return list<int>
 */
function get_download_ids( $order ) {
	$download_ids = array_map(
		fn( $item ) => absint( $item->product_id ),
		$order->get_items()
	);
	return array_values( array_unique( array_filter( $download_ids ) ) );
}

/**
 * Get EDD order actions.
 *
 * @param TGoal $goal Goal.
 *
 * @return list<TEdd_Order_Attributes>
 */
function get_edd_order_actions( $goal ) {
	$is_edd_order = function ( $action ) {
		/** @var TConversion_Action $action */
		return 'nab/edd-order' === $action['type'];
	};

	$actions = $goal['conversionActions'];
	$actions = array_values( array_filter( $actions, $is_edd_order ) );
	/** @var list<TEdd_Order_Attributes> */
	return wp_list_pluck( $actions, 'attributes' );
}

/**
 * Returns expected statuses.
 *
 * @param TGoal $goal Goal.
 *
 * @return list<string>
 */
function get_expected_statuses( $goal ) {
	$attrs  = $goal['attributes'];
	$status = isset( $attrs['orderStatusForConversion'] ) ? $attrs['orderStatusForConversion'] : 'complete';

	/**
	 * Returns the statuses that might trigger a conversion when there’s an Easy Digital Downloads order.
	 * Don’t include the `edd-` prefix in status names.
	 *
	 * @param list<string>|string $statuses the status (or statuses) that might trigger a conversion.
	 *
	 * @since 6.0.0
	 */
	$expected_statuses = apply_filters( 'nab_edd_order_status_for_conversions', $status );
	if ( ! is_array( $expected_statuses ) ) {
		$expected_statuses = array( $expected_statuses );
	}

	return $expected_statuses;
}
