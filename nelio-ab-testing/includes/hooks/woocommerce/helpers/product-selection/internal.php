<?php
namespace Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\Internal;

defined( 'ABSPATH' ) || exit;

function do_products_match_by_id( $selection, $product_ids ) {
	$actual_pids   = add_parent_products( $product_ids );
	$tracked_pids  = $selection['productIds'];
	$matching_pids = array_intersect( $actual_pids, $tracked_pids );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'or' === $mode
			? empty( $matching_pids )
			: count( $matching_pids ) < count( $tracked_pids );
	}//end if

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $tracked_pids ) === count( $matching_pids )
		: ! empty( $matching_pids );
}//end do_products_match_by_id()

function do_products_match_by_taxonomy( $selection, $product_ids ) {
	$actual_pids    = add_parent_products( $product_ids );
	$tracked_terms  = $selection['termIds'];
	$actual_terms   = get_all_terms( $selection['taxonomy'], $actual_pids );
	$matching_terms = array_intersect( $actual_terms, $tracked_terms );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_terms )
			: count( $matching_terms ) < $tracked_terms;
	}//end if

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $matching_terms ) === count( $tracked_terms )
		: ! empty( $matching_terms );
}//end do_products_match_by_taxonomy()

function add_parent_products( $product_ids ) {
	$product_ids = array_map(
		function ( $pid ) {
			$query   = new \WP_Query(
				array(
					'post__in'  => array( $pid ),
					'post_type' => array( 'product', 'product_variation' ),
					'fields'    => 'id=>parent',
				)
			);
			$results = $query->get_posts();
			$parent  = $results[ "post_parent:{$pid}" ];
			return empty( $parent ) ? array( $pid ) : array( $pid, $parent );
		},
		$product_ids
	);
	return array_values( array_unique( array_merge( array(), ...$product_ids ) ) );
}//end add_parent_products()

function get_all_terms( $taxonomy, $product_ids ) {
	$term_ids = array_map(
		function ( $pid ) use ( $taxonomy ) {
			$terms = wp_get_post_terms( $pid, $taxonomy, array( 'fields' => 'ids' ) );
			return is_wp_error( $terms ) ? array() : $terms;
		},
		$product_ids
	);
	return array_values( array_unique( array_merge( array(), ...$term_ids ) ) );
}//end get_all_terms()

function get_conversion_value( $order, $goal ) {
	$attrs       = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	$use_revenue = ! empty( $attrs['useOrderRevenue'] );
	return $use_revenue ? ( 0 + $order->get_total() ) : 0;
}//end get_conversion_value()

function get_expected_statuses( $goal ) {
	$attrs  = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	$status = isset( $attrs['orderStatusForConversion'] ) ? $attrs['orderStatusForConversion'] : 'wc-completed';
	$status = str_replace( 'wc-', '', $status );

	/**
	 * Returns the statuses that might trigger a conversion when there’s a WooCommerce order.
	 * Don’t include the `wc-` prefix in status names.
	 *
	 * @param array|string $statuses the status (or statuses) that might trigger a conversion.
	 *
	 * @since 5.0.0
	 */
	$expected_statuses = apply_filters( 'nab_order_status_for_conversions', $status );
	if ( ! is_array( $expected_statuses ) ) {
		$expected_statuses = array( $expected_statuses );
	}//end if

	return $expected_statuses;
}//end get_expected_statuses()
