<?php
namespace Nelio_AB_Testing\WooCommerce\Compat\Woo_Product_Bundle;

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'WOOSB_DIR' ) ) {
			return;
		}

		add_action( 'init', __NAMESPACE__ . '\fix_bundled_product_pricing_in_cart_and_checkout_screen' );
	}
);

/**
 * Callback to fix bundled product prices in cart and checkout screen.
 *
 * The plugin "WPC Product Bundles for WooCommerce" (woo-product-bundle) calculates the
 * final price of bundled products during the `woocommerce_before_calculate_totals`
 * action. At that point, it updates the price of the product instance stored in each
 * `cart_item`. Later, the cart/checkout screens simply call `get_price()` on that
 * product instance, which returns the already-calculated bundled price.
 *
 * When an A/B test is running, this behavior breaks. Calling `get_price()` triggers
 * our price replacement logic, which ignores the price already set by the bundle
 * plugin and recomputes the price based on the experiment. As a result, the bundled
 * discount is effectively lost.
 *
 * To avoid this conflict, we keep track of product instances that belong to bundles.
 * During `woocommerce_before_calculate_totals`, when the bundle plugin updates the
 * bundled prices, we store those product instances in a local cache
 * (`$products_in_bundles`). For example, if a product normally costs $10, variant B
 * sets it to $8, and the bundle applies a 10% discount, the bundle plugin stores the
 * final price ($7.20) in the cart item’s product instance.
 *
 * When `get_price()` is later called on a product instance that exists in
 * `$products_in_bundles`, we bypass our pricing hooks. This ensures we return the
 * bundle-adjusted price ($7.20) instead of replacing it with the experiment price
 * (e.g., $8).
 *
 * @return void
 */
function fix_bundled_product_pricing_in_cart_and_checkout_screen() {
	$products_in_bundles = array();

	add_action(
		'woocommerce_before_calculate_totals',
		function () use ( &$products_in_bundles ) {
			$products_in_bundles = array();
		},
		9999 - 1
	);

	add_action(
		'woocommerce_before_calculate_totals',
		function ( $cart_object ) use ( &$products_in_bundles ) {
			/** @var object{cart_contents:array<array{woosb_parent_id?:int,data:\WC_Product}>} $cart_object */

			$cart_contents = $cart_object->cart_contents;
			foreach ( $cart_contents as $item ) {
				if ( ! empty( $item['woosb_parent_id'] ) ) {
					$products_in_bundles[] = $item['data'];
				}
			}
		},
		9999 + 1
	);

	add_filter(
		'nab_exclude_woocommerce_product_from_price_testing',
		function ( $r, $p, $pt ) use ( &$products_in_bundles ) {
			if ( 'price' !== $pt ) {
				return $r;
			}
			if ( ! is_object( $p ) ) {
				return $r;
			}
			return $r || in_array( $p, $products_in_bundles, true );
		},
		10,
		3
	);
}
