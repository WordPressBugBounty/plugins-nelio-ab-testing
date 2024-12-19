<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function Nelio_AB_Testing\WooCommerce\Helpers\Actions\notify_alternative_loaded;

function load_alternative_discount( $alternative, $control, $experiment_id ) {

	add_filter(
		'nab_enable_custom_woocommerce_hooks',
		function ( $enabled, $product_id ) use ( $control, $experiment_id ) {
			return $enabled || is_product_under_test( $experiment_id, $control, $product_id );
		},
		10,
		2
	);


	$get_sale_price = function ( $sale_price, $product_id, $regular_price ) use ( $control, $alternative, $experiment_id ) {
		if ( ! is_numeric( $regular_price ) ) {
			return $sale_price;
		}//end if

		if ( ! is_product_under_test( $experiment_id, $control, $product_id ) ) {
			return $sale_price;
		}//end if

		notify_alternative_loaded( $experiment_id );
		if ( empty( $alternative['discount'] ) ) {
			return $sale_price;
		}//end if

		$was_already_on_sale = $sale_price < $regular_price;
		if ( $was_already_on_sale && empty( $alternative['overwritesExistingSalePrice'] ) ) {
			return $sale_price;
		}//end if

		return $regular_price * ( 100 - $alternative['discount'] ) / 100;
	};
	add_nab_filter( 'woocommerce_product_sale_price', $get_sale_price, 99, 3 );
	add_nab_filter( 'woocommerce_variation_sale_price', $get_sale_price, 99, 3 );
}//end load_alternative_discount()
add_action( 'nab_nab/wc-bulk-sale_load_alternative', __NAMESPACE__ . '\load_alternative_discount', 10, 3 );
