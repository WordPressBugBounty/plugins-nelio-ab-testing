<?php

namespace Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection;

use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\Internal\do_products_match_by_id;
use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\Internal\do_products_match_by_taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the given product is a variable product or not.
 *
 * @param \WC_Product|null $product Product.
 *
 * @return bool
 */
function is_variable_product( $product ) {
	/**
	 * Filters the list of product types that should be considered as variable products.
	 *
	 * @param list<string> $types List of product types. Default: `array( 'variable' )`.
	 *
	 * @since 6.5.0
	 */
	$variable_product_types = apply_filters( 'nab_woocommerce_variable_product_types', array( 'variable' ) );
	return ! empty( $product ) && in_array( $product->get_type(), $variable_product_types, true );
}

/**
 * Whether the purchased products match the tracked selection.
 *
 * @param TWC_Product_Selection $product_selection Tracked selection.
 * @param int|list<int>         $product_ids       Purchased product IDs.
 * @return bool
 */
function do_products_match( $product_selection, $product_ids ) {
	if ( ! is_array( $product_ids ) ) {
		$product_ids = array( $product_ids );
	}

	if ( 'all-products' === $product_selection['type'] ) {
		return true;
	}

	$selection = $product_selection['value'];
	switch ( $selection['type'] ) {
		case 'product-ids':
			return do_products_match_by_id( $selection, $product_ids );

		case 'product-taxonomies':
			return nab_every(
				function ( $product_term_selection ) use ( &$product_ids ) {
					return do_products_match_by_taxonomy( $product_term_selection, $product_ids );
				},
				$selection['value']
			);

		default:
			return false;
	}
}
