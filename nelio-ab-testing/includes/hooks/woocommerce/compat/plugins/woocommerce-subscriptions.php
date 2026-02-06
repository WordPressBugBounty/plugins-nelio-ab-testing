<?php
namespace Nelio_AB_Testing\WooCommerce\Compat\WooSubscriptions;

defined( 'ABSPATH' ) || exit;

use function wc_get_product;

add_action(
	'woocommerce_init',
	function () {
		add_filter( 'nab_woocommerce_variable_product_types', __NAMESPACE__ . '\add_variable_subscriptions_as_variable_products' );
		add_action( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\add_hooks_to_load_variable_price_summary', 10, 2 );
		add_action( 'nab_nab/wc-product_preview_alternative', __NAMESPACE__ . '\add_hooks_to_load_variable_price_summary', 10, 2 );
	}
);

/**
 * Callback to add variable subscriptions as variable products.
 *
 * @param list<string> $types Types.
 *
 * @return list<string>
 */
function add_variable_subscriptions_as_variable_products( $types ) {
	$types[] = 'variable-subscription';
	return $types;
}

/**
 * Callback to add hooks to load variable price summary.
 *
 * @param TWC_Product_Alternative_Attributes|TWC_Product_Control_Attributes $alternative Alternative.
 * @param TWC_Product_Control_Attributes                                    $control Control.
 *
 * @return void
 */
function add_hooks_to_load_variable_price_summary( $alternative, $control ) {
	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];
	if ( $control_id === $alternative_id ) {
		return;
	}

	$control = wc_get_product( $control_id );
	if ( empty( $control ) || 'variable-subscription' !== $control->get_type() ) {
		return;
	}

	$alternative = get_post( $alternative_id, ARRAY_A );
	if ( empty( $alternative ) ) {
		return;
	}

	$variation_data = get_post_meta( $alternative_id, '_nab_variation_data', true );
	if ( empty( $variation_data ) ) {
		$variation_data = array();
	}

	add_filter(
		'woocommerce_subscriptions_product_price',
		function ( $price, $product ) use ( &$variation_data ) {
			/** @var string      $price   */
			/** @var \WC_Product $product */

			$id   = $product->get_id();
			$data = isset( $variation_data[ $id ] ) ? $variation_data[ $id ] : array();
			return isset( $data['salePrice'] ) ? $data['salePrice'] : $price;
		},
		10,
		2
	);
}
