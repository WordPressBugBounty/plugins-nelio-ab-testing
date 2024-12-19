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

function add_hooks_for_tracking( $action, $experiment_id, $goal_index, $goal ) {

	add_action(
		'edd_built_order',
		function ( $order_id ) {
			$experiments = nab_get_experiments_with_page_view_from_request();
			if ( empty( $experiments ) ) {
				return;
			}//end if
			edd_update_order_meta( $order_id, '_nab_experiments_with_page_view', $experiments );

			$unique_ids = nab_get_unique_views_from_request();
			if ( ! empty( $unique_ids ) ) {
				edd_update_order_meta( $order_id, '_nab_unique_ids', $unique_ids );
			}//end if

			$segments = nab_get_segments_from_request();
			if ( ! empty( $segments ) ) {
				edd_update_order_meta( $order_id, '_nab_segments', $segments );
			}//end if
		}
	);

	add_action(
		'edd_update_payment_status',
		function ( $order_id, $new_status, $old_status ) use ( $action, $experiment_id, $goal_index, $goal ) {
			if ( $old_status === $new_status ) {
				return;
			}//end if

			if ( ! function_exists( 'edd_get_order_meta' ) ) {
				return;
			}//end if

			$synched_goals = edd_get_order_meta( $order_id, '_nab_synched_goals', true );
			$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();
			if ( in_array( "{$experiment_id}:{$goal_index}", $synched_goals, true ) ) {
				return;
			}//end if

			$expected_statuses = get_expected_statuses( $goal );
			if ( ! in_array( $new_status, $expected_statuses, true ) ) {
				return;
			}//end if

			$experiments = edd_get_order_meta( $order_id, '_nab_experiments_with_page_view', true );
			if ( empty( $experiments ) || ! isset( $experiments[ $experiment_id ] ) ) {
				return;
			}//end if

			if ( ! function_exists( 'edd_get_order' ) ) {
				return;
			}//end if

			$order = edd_get_order( $order_id );

			$download_ids = get_download_ids( $order );
			if ( ! do_downloads_match( $action['value'], $download_ids ) ) {
				return;
			}//end if

			$value       = get_conversion_value( $order, $goal );
			$alternative = $experiments[ $experiment_id ];
			$options     = array( 'value' => $value );

			$unique_ids = edd_get_order_meta( $order_id, '_nab_unique_ids', true );
			if ( isset( $unique_ids[ $experiment_id ] ) ) {
				$options['unique_id'] = $unique_ids[ $experiment_id ];
			}//end if

			$segments = edd_get_order_meta( $order_id, '_nab_segments', true );
			if ( isset( $segments[ $experiment_id ] ) ) {
				$options['segments'] = $segments[ $experiment_id ];
			}//end if

			nab_track_conversion( $experiment_id, $goal_index, $alternative, $options );
			array_push( $synched_goals, "{$experiment_id}:{$goal_index}" );
			edd_update_order_meta( $order_id, '_nab_synched_goals', $synched_goals );
		},
		10,
		3
	);
}//end add_hooks_for_tracking()
add_action( 'nab_nab/edd-order_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 4 );

function get_conversion_value( $order, $goal ) {
	$attrs = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	if ( empty( $attrs['useOrderRevenue'] ) ) {
		return 0;
	}//end if

	/**
	 * Filters which products in an order contribute to the conversion revenue.
	 *
	 * In Easy Digital Downloads order conversion actions, when there’s a new
	 * order containing tracked downloads, this filter specifies whether it
	 * should track the order total or only the value of the tracked downloads.
	 *
	 * @param boolean   $track_order_total Default: `false`.
	 * @param EDD_Order $order             The order.
	 *
	 * @since 6.4.0
	 */
	if ( apply_filters( 'nab_track_edd_order_total', false, $order ) ) {
		return filter_order_value( 0 + $order->get_total(), $order );
	}//end if

	$actions         = get_edd_order_actions( $goal );
	$is_tracked_item = function ( $item ) use ( &$actions ) {
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
}//end get_conversion_value()

function filter_order_value( $value, $order ) {
	/**
	 * Filters the value of an EDD order.
	 *
	 * @param number    $value the order value (be it the full order or just the relevant items in it).
	 * @param EDD_Order $order the order.
	 *
	 * @since 6.4.0
	 */
	$value = apply_filters( 'nab_edd_order_value', $value, $order );
	return 0 + $value;
}//end filter_order_value()

function get_download_ids( $order ) {
	$download_ids = array_map(
		function ( $item ) {
			return absint( $item->product_id );
		},
		$order->get_items()
	);
	return array_values( array_unique( array_filter( $download_ids ) ) );
}//end get_download_ids()

function get_edd_order_actions( $goal ) {

	$is_edd_order = function ( $action ) {
		return 'nab/edd-order' === $action['type'];
	};

	$add_attributes = function ( $action ) {
		$action['attributes'] = isset( $action['attributes'] )
			? $action['attributes']
			: array();
		return $action;
	};

	$actions = isset( $goal['conversionActions'] ) ? $goal['conversionActions'] : array();
	$actions = array_filter( $actions, $is_edd_order );
	$actions = array_map( $add_attributes, $actions );
	$actions = wp_list_pluck( $actions, 'attributes' );
	$actions = array_values( array_filter( $actions ) );
	return array_values( array_filter( $actions ) );
}//end get_edd_order_actions()

function get_expected_statuses( $goal ) {
	$attrs  = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	$status = isset( $attrs['orderStatusForConversion'] ) ? $attrs['orderStatusForConversion'] : 'complete';

	/**
	 * Returns the statuses that might trigger a conversion when there’s an Easy Digital Downloads order.
	 * Don’t include the `edd-` prefix in status names.
	 *
	 * @param array|string $statuses the status (or statuses) that might trigger a conversion.
	 *
	 * @since 6.0.0
	 */
	$expected_statuses = apply_filters( 'nab_edd_order_status_for_conversions', $status );
	if ( ! is_array( $expected_statuses ) ) {
		$expected_statuses = array( $expected_statuses );
	}//end if

	return $expected_statuses;
}//end get_expected_statuses()
