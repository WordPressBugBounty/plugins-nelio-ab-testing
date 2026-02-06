<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Add_To_Cart;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Summarizes conversion action.
 *
 * @param TWC_Add_To_Cart_Conversion_Action_Attributes $attributes Attributes.
 *
 * @return array{anyProduct:true}|array{productIds:list<int>}
 */
function summarize_conversion_action( $attributes ) {
	if ( ! empty( $attributes['anyProduct'] ) ) {
		return array( 'anyProduct' => true );
	}

	$product_id = absint( $attributes['productId'] );
	$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
	if ( empty( $product ) ) {
		return array( 'productIds' => array( $product_id ) );
	}

	$product_ids = array_merge(
		array( $product->get_id() ),
		$product->get_children()
	);
	$product_ids = array_map( fn( $id ) => absint( $id ), $product_ids );
	$product_ids = array_values( array_filter( $product_ids ) );
	return array( 'productIds' => $product_ids );
}
add_filter( 'nab_get_nab/wc-add-to-cart_conversion_action_summary', __NAMESPACE__ . '\summarize_conversion_action' );
