<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;

function add_hooks_for_tracking( $action, $experiment_id, $goal_index, $goal ) {

	add_action(
		'surecart/checkout_confirmed',
		function ( $checkout, $request ) use ( $action, $experiment_id, $goal_index, $goal ) {

			$experiments = nab_get_experiments_with_page_view_from_request( $request );
			if ( empty( $experiments ) ) {
				return;
			}//end if

			$metadata                                    = array();
			$metadata['_nab_experiments_with_page_view'] = $experiments;

			if ( ! isset( $experiments[ $experiment_id ] ) ) {
				return;
			}//end if

			$product_ids = get_product_ids( $checkout );
			if ( ! do_products_match( $action['value'], $product_ids ) ) {
				return;
			}//end if

			$value       = get_conversion_value( $checkout, $goal );
			$alternative = $experiments[ $experiment_id ];
			$options     = array( 'value' => $value );

			$unique_ids = nab_get_unique_views_from_request( $request );
			if ( ! empty( $unique_ids ) ) {
				$metadata['_nab_unique_ids'] = $unique_ids;
			}//end if
			if ( isset( $unique_ids[ $experiment_id ] ) ) {
				$options['unique_id'] = $unique_ids[ $experiment_id ];
			}//end if

			$segments = nab_get_segments_from_request( $request );
			if ( ! empty( $segments ) ) {
				$metadata['_nab_segments'] = $segments;
			}//end if
			if ( isset( $segments[ $experiment_id ] ) ) {
				$options['segments'] = $segments[ $experiment_id ];
			}//end if

			$metadata['_nab_synched_goals'] = array( "{$experiment_id}:{$goal_index}" );

			nab_track_conversion( $experiment_id, $goal_index, $alternative, $options );

			$checkout_object   = ( new \SureCart\Models\Checkout() )->find( $checkout->getAttribute( 'id' ) );
			$checkout_metadata = (array) $checkout_object->getAttribute( 'metadata' ) ?? array();
			$existing_metadata = $checkout_metadata['nabmetadata'] ?? array();
			foreach ( $metadata as $key => $value ) {
				$metadata[ $key ] = nab_array_merge(
					json_decode( $existing_metadata[ $key ] ?? '[]', ARRAY_A ),
					$value
				);
			}//end foreach

			$existing_metadata['nabmetadata'] = wp_json_encode( $metadata );

			$checkout_object->setAttribute( 'metadata', $existing_metadata );
			$checkout_object->save();
		},
		10,
		2
	);
}//end add_hooks_for_tracking()
add_action( 'nab_nab/surecart-order_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 4 );

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
}//end get_product_ids()

function get_conversion_value( $checkout, $goal ) {
	$attrs = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	if ( empty( $attrs['useOrderRevenue'] ) ) {
		return 0;
	}//end if

	/**
	 * Filters which products in an order contribute to the conversion revenue.
	 *
	 * In SureCart order conversion actions, when there’s a new
	 * order containing tracked products, this filter specifies whether it
	 * should track the order total or only the value of the tracked downloads.
	 *
	 * @param boolean   $track_order_total Default: `false`.
	 * @param SureCart\Models\Checkout $checkout             The checkout data.
	 *
	 * @since 7.2.0
	 */
	if ( apply_filters( 'nab_track_surecart_order_total', false, $checkout ) ) {
		return filter_order_value( 0 + ( $checkout->getAttribute( 'total_amount' ) / 100 ), $checkout );
	}//end if

	$actions         = get_surecart_order_actions( $goal );
	$is_tracked_item = function ( $item ) use ( &$actions ) {
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
			return $carry + ( $item->getAttribute( 'total_amount' ) / 100 );
		},
		0
	);
	return filter_order_value( $value, $checkout );
}//end get_conversion_value()

function filter_order_value( $value, $checkout ) {
	/**
	 * Filters the value of a SureCart order.
	 *
	 * @param number    $value the order value (be it the full order or just the relevant items in it).
	 * @param SureCart\Models\Checkout $checkout the checkout data.
	 *
	 * @since 7.2.0
	 */
	$value = apply_filters( 'nab_surecart_order_value', $value, $checkout );
	return 0 + $value;
}//end filter_order_value()

function get_surecart_order_actions( $goal ) {

	$is_surecart_order = function ( $action ) {
		return 'nab/surecart-order' === $action['type'];
	};

	$add_attributes = function ( $action ) {
		$action['attributes'] = isset( $action['attributes'] )
			? $action['attributes']
			: array();
		return $action;
	};

	$actions = isset( $goal['conversionActions'] ) ? $goal['conversionActions'] : array();
	$actions = array_filter( $actions, $is_surecart_order );
	$actions = array_map( $add_attributes, $actions );
	$actions = wp_list_pluck( $actions, 'attributes' );
	$actions = array_values( array_filter( $actions ) );
	return array_values( array_filter( $actions ) );
}//end get_surecart_order_actions()

function do_products_match( $product_selection, $product_ids ) {
	if ( ! is_array( $product_ids ) ) {
		$product_ids = array( $product_ids );
	}//end if

	if ( 'all-surecart-products' === $product_selection['type'] ) {
		return true;
	}//end if

	if ( 'some-surecart-products' !== $product_selection['type'] ) {
		return false;
	}//end if

	$selection = $product_selection['value'];
	switch ( $selection['type'] ) {
		case 'surecart-ids':
			return do_products_match_by_id( $selection, $product_ids );

		default:
			return false;
	}//end switch
}//end do_products_match()

function do_products_match_by_id( $selection, $product_ids ) {
	$tracked_pids  = $selection['productIds'];
	$matching_pids = array_intersect( $product_ids, $tracked_pids );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_dids )
			: count( $matching_pids ) < $tracked_pids;
	}//end if

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $tracked_pids ) === count( $matching_pids )
		: ! empty( $tracked_pids );
}//end do_products_match_by_id()
