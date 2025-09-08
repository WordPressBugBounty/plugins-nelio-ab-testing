<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Add_To_Cart;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_get_nab/wc-add-to-cart_conversion_action_summary',
	function ( $attributes ) {
		if ( nab_array_get( $attributes, 'anyProduct', false ) ) {
			return $attributes;
		}//end if

		$product_id = nab_array_get( $attributes, 'productId', 0 );
		$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( empty( $product ) ) {
			return $attributes;
		}//end if

		$product_ids = array_merge(
			array( $product->get_id() ),
			$product->get_children()
		);
		return array( 'productIds' => $product_ids );
	}
);
