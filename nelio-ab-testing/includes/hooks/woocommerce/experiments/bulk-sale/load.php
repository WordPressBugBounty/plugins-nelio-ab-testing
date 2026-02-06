<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function Nelio_AB_Testing\WooCommerce\Helpers\Actions\notify_alternative_loaded;

add_filter(
	'nab_is_nab/wc-bulk-sale_relevant_in_ajax_request',
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	fn( $r ) => $r || isset( $_REQUEST['wc-ajax'] )
);

add_filter(
	'nab_is_nab/wc-bulk-sale_relevant_in_rest_request',
	'__return_true'
);

/**
 * Callback to load alternative discount.
 *
 * @param TWC_Bulk_Sale_Alternative_Attributes|TWC_Bulk_Sale_Control_Attributes $alternative   Alternative.
 * @param TWC_Bulk_Sale_Control_Attributes                                      $control       Control.
 * @param int                                                                   $experiment_id Experiment ID.
 *
 * @return void
 */
function load_alternative_discount( $alternative, $control, $experiment_id ) {

	add_filter(
		'nab_enable_custom_woocommerce_hooks',
		function ( $enabled, $product_id ) use ( $control, $experiment_id ) {
			/** @var bool $enabled    */
			/** @var int  $product_id */
			return $enabled || is_product_under_test( $experiment_id, $control, $product_id );
		},
		10,
		2
	);


	$get_sale_price = function ( $sale_price, $product_id, $regular_price ) use ( $control, $alternative, $experiment_id ) {
		/** @var string $sale_price    */
		/** @var int    $product_id    */
		/** @var string $regular_price */

		if ( ! is_numeric( $regular_price ) ) {
			return $sale_price;
		}

		if ( ! is_product_under_test( $experiment_id, $control, $product_id ) ) {
			return $sale_price;
		}

		notify_alternative_loaded( $experiment_id );
		if ( empty( $alternative['discount'] ) ) {
			return $sale_price;
		}

		$was_already_on_sale = $sale_price < $regular_price;
		if ( $was_already_on_sale && empty( $alternative['overwritesExistingSalePrice'] ) ) {
			return $sale_price;
		}

		return $regular_price * ( 100 - $alternative['discount'] ) / 100;
	};
	nab_add_filter( 'woocommerce_product_sale_price', $get_sale_price, 99, 3 );
	nab_add_filter( 'woocommerce_variation_sale_price', $get_sale_price, 99, 3 );
}
add_action( 'nab_nab/wc-bulk-sale_load_alternative', __NAMESPACE__ . '\load_alternative_discount', 10, 3 );
