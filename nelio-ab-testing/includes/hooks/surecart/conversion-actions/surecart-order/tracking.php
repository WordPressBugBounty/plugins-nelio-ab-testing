<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;

/**
 * Adds hooks for tracking EDD’s order completion.
 *
 * @param TSureCart_Order_Attributes $action Action.
 * @param int                        $experiment_id Experiment ID.
 * @param int                        $goal_index Goal index.
 * @param TGoal                      $goal Goal.
 *
 * @return void
 */
function add_hooks_for_tracking( $action, $experiment_id, $goal_index, $goal ) {

	add_action(
		'surecart/checkout_confirmed',
		function ( $checkout, $request ) use ( $action, $experiment_id, $goal_index, $goal ) {
			/** @var \SureCart\Models\Checkout             $checkout */
			/** @var \WP_REST_Request<array<string,mixed>> $request  */

			$experiments = nab_get_experiments_with_page_view_from_request( $request );
			if ( empty( $experiments ) ) {
				return;
			}

			$metadata                                    = array();
			$metadata['_nab_experiments_with_page_view'] = $experiments;

			if ( ! isset( $experiments[ $experiment_id ] ) ) {
				return;
			}

			$product_ids = get_product_ids( $checkout );
			if ( ! do_products_match( $action['value'], $product_ids ) ) {
				return;
			}

			$value       = get_conversion_value( $checkout, $goal );
			$alternative = $experiments[ $experiment_id ];
			$options     = array( 'value' => $value );

			$unique_ids = nab_get_unique_views_from_request( $request );
			if ( ! empty( $unique_ids ) ) {
				$metadata['_nab_unique_ids'] = $unique_ids;
			}
			if ( isset( $unique_ids[ $experiment_id ] ) ) {
				$options['unique_id'] = $unique_ids[ $experiment_id ];
			}

			$segments = nab_get_segments_from_request( $request );
			if ( ! empty( $segments ) ) {
				$metadata['_nab_segments'] = $segments;
			}
			if ( isset( $segments[ $experiment_id ] ) ) {
				$options['segments'] = $segments[ $experiment_id ];
			}

			$metadata['_nab_synched_goals'] = array( "{$experiment_id}:{$goal_index}" );

			$ga4_client_id = nab_get_ga4_client_id_from_request();
			if ( ! empty( $ga4_client_id ) ) {
				$metadata['_nab_ga4_client_id'] = array( "{$ga4_client_id}" );
				$options['ga4_client_id']       = $ga4_client_id;
			}

			$checkout_object = ( new \SureCart\Models\Checkout() )->find( $checkout->getAttribute( 'id' ) );
			if ( is_wp_error( $checkout_object ) ) {
				return;
			}

			nab_track_conversion( $experiment_id, $goal_index, $alternative, $options );

			$checkout_metadata = $checkout_object->getAttribute( 'metadata' );
			$existing_metadata = $checkout_metadata->nabmetadata ?? '';
			$existing_metadata = json_decode( $existing_metadata, true );
			$existing_metadata = is_array( $existing_metadata ) ? $existing_metadata : array();
			$existing_metadata = wp_parse_args( $existing_metadata, $metadata );
			foreach ( $metadata as $key => $value ) {
				/** @var array<int,mixed> */
				$prev_value       = $existing_metadata[ $key ] ?? array();
				$metadata[ $key ] = numeric_key_array_merge( $prev_value, $value );
			}

			$existing_metadata['nabmetadata'] = wp_json_encode( $metadata );

			$checkout_object->setAttribute( 'metadata', $existing_metadata );
			$checkout_object->save();
		},
		10,
		2
	);
}
add_action( 'nab_nab/surecart-order_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 4 );

/**
 * Returns the product IDs purchased in the checkout.
 *
 * @param \SureCart\Models\Checkout $checkout Checkout.
 *
 * @return list<string>
 */
function get_product_ids( $checkout ) {
	$product_ids = array_map(
		function ( $item ) {
			$price   = $item->getAttribute( 'price' );
			$product = $price->getAttribute( 'product' );
			return $product->getAttribute( 'id' );
		},
		$checkout->getAttribute( 'line_items' )->data
	);
	return array_values( array_unique( array_filter( $product_ids ) ) );
}

/**
 * Retuns conversion value.
 *
 * @param \SureCart\Models\Checkout $checkout Order.
 * @param TGoal                     $goal     Goal.
 *
 * @return float
 */
function get_conversion_value( $checkout, $goal ) {
	$attrs = $goal['attributes'];
	if ( empty( $attrs['useOrderRevenue'] ) ) {
		return 0;
	}

	/**
	 * Filters which products in an order contribute to the conversion revenue.
	 *
	 * In SureCart order conversion actions, when there’s a new
	 * order containing tracked products, this filter specifies whether it
	 * should track the order total or only the value of the tracked downloads.
	 *
	 * @param boolean                   $track_order_total Default: `false`.
	 * @param \SureCart\Models\Checkout $checkout          The checkout data.
	 *
	 * @since 7.2.0
	 */
	if ( apply_filters( 'nab_track_surecart_order_total', false, $checkout ) ) {
		return filter_order_value( 0 + ( $checkout->getAttribute( 'total_amount' ) / 100 ), $checkout );
	}

	$actions         = get_surecart_order_actions( $goal );
	$is_tracked_item = function ( $item ) use ( &$actions ) {
		/** @var \SureCart\Models\LineItem $item */
		$price      = $item->getAttribute( 'price' );
		$product    = $price->getAttribute( 'product' );
		$product_id = $product->getAttribute( 'id' );
		return nab_some(
			function ( $action ) use ( $product_id ) {
				return do_products_match( $action['value'], $product_id );
			},
			$actions
		);
	};

	$items = array_filter( $checkout->getAttribute( 'line_items' )->data, $is_tracked_item );
	$items = array_values( $items );

	$value = array_reduce(
		$items,
		function ( $carry, $item ) {
			/** @var int                       $carry */
			/** @var \SureCart\Models\LineItem $item  */
			return $carry + ( $item->getAttribute( 'total_amount' ) / 100 );
		},
		0
	);
	return filter_order_value( $value, $checkout );
}

/**
 * Filters order value.
 *
 * @param number                    $value    Actual value.
 * @param \SureCart\Models\Checkout $checkout Order.
 *
 * @return number
 */
function filter_order_value( $value, $checkout ) {
	/**
	 * Filters the value of a SureCart order.
	 *
	 * @param number    $value the order value (be it the full order or just the relevant items in it).
	 * @param \SureCart\Models\Checkout $checkout the checkout data.
	 *
	 * @since 7.2.0
	 */
	$value = apply_filters( 'nab_surecart_order_value', $value, $checkout );
	return 0 + $value;
}

/**
 * Get EDD order actions.
 *
 * @param TGoal $goal Goal.
 *
 * @return list<TSureCart_Order_Attributes>
 */
function get_surecart_order_actions( $goal ) {

	$is_surecart_order = function ( $action ) {
		/** @var TConversion_Action $action */
		return 'nab/surecart-order' === $action['type'];
	};

	$actions = $goal['conversionActions'];
	$actions = array_values( array_filter( $actions, $is_surecart_order ) );
	/** @var list<TSureCart_Order_Attributes> */
	return wp_list_pluck( $actions, 'attributes' );
}

/**
 * Whether the purchased products match the tracked selection.
 *
 * @param TSureCart_Product_Selection $product_selection Tracked selection.
 * @param string|list<string>         $product_ids       Purchased product IDs.
 * @return bool
 */
function do_products_match( $product_selection, $product_ids ) {
	if ( ! is_array( $product_ids ) ) {
		$product_ids = array( $product_ids );
	}

	if ( 'all-surecart-products' === $product_selection['type'] ) {
		return true;
	}

	$selection = $product_selection['value'];
	switch ( $selection['type'] ) {
		case 'surecart-ids':
			return do_products_match_by_id( $selection, $product_ids );

		default:
			return false;
	}
}

/**
 * Whether the downloaded items match the tracked selection.
 *
 * @param TSureCart_Selected_Product_Ids $selection   Tracked selection.
 * @param list<string>                   $product_ids Purchased product IDs.
 * @return bool
 */
function do_products_match_by_id( $selection, $product_ids ) {
	$tracked_pids  = $selection['productIds'];
	$matching_pids = array_intersect( $product_ids, $tracked_pids );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_pids )
			: count( $matching_pids ) < count( $tracked_pids );
	}

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $tracked_pids ) === count( $matching_pids )
		: ! empty( $tracked_pids );
}

/**
 * Merges two arrays with numeric keys. If a key is duplicated, the value in `$b` is kept.
 *
 * @param array<int,mixed> $a Array.
 * @param array<int,mixed> $b Array.
 *
 * @return array<int,mixed>
 */
function numeric_key_array_merge( $a, $b ) {
	$a = array_combine( array_map( fn( $k ) => " $k ", array_keys( $a ) ), $a );
	$b = array_combine( array_map( fn( $k ) => " $k ", array_keys( $b ) ), $b );
	$c = array_merge( $a, $b );
	return array_combine( array_map( fn( $k ) => absint( trim( $k ) ), array_keys( $c ) ), $c );
}
