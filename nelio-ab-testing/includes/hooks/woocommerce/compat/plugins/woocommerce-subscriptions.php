<?php
namespace Nelio_AB_Testing\WooCommerce\Compat\WooSubscriptions;

defined( 'ABSPATH' ) || exit;

use function wc_get_product;

add_action(
	'woocommerce_init',
	function () {
		add_filter( 'nab_woocommerce_variable_product_types', __NAMESPACE__ . '\add_variable_subscriptions_as_variable_products' );
		add_filter( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\add_hooks_to_load_variable_price_summary', 10, 2 );
		add_filter( 'nab_nab/wc-product_preview_alternative', __NAMESPACE__ . '\add_hooks_to_load_variable_price_summary', 10, 2 );
	}
);

function add_variable_subscriptions_as_variable_products( $types ) {
	$types[] = 'variable-subscription';
	return $types;
}//end add_variable_subscriptions_as_variable_products()

function add_hooks_to_load_variable_price_summary( $alternative, $control ) {
	$control_id     = $control['postId'];
	$alternative_id = isset( $alternative['postId'] ) ? $alternative['postId'] : 0;
	if ( $control_id === $alternative_id ) {
		return;
	}//end if

	$control = wc_get_product( $control_id );
	if ( empty( $control ) || 'variable-subscription' !== $control->get_type() ) {
		return;
	}//end if

	$alternative = get_post( $alternative_id, ARRAY_A );
	if ( empty( $alternative ) ) {
		return;
	}//end if

	$variation_data = get_post_meta( $alternative_id, '_nab_variation_data', true );
	if ( empty( $variation_data ) || ! is_array( $variation_data ) ) {
		$variation_data = array();
	}//end if

	add_filter(
		'woocommerce_subscriptions_product_price',
		function ( $price, $product ) use ( &$variation_data ) {
			$id   = $product->get_id();
			$data = isset( $variation_data[ $id ] ) ? $variation_data[ $id ] : array();
			return isset( $data['salePrice'] ) ? $data['salePrice'] : $price;
		},
		10,
		2
	);
}//end add_hooks_to_load_variable_price_summary()
