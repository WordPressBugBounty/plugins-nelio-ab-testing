<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use WC_Product;

use function add_action;
use function add_filter;

function register_alt_product_type() {
	if ( class_exists( 'WC_Product' ) ) {
		require_once __DIR__ . '/class-alternative-product.php';
	}//end if
}//end register_alt_product_type()
add_action( 'init', __NAMESPACE__ . '\register_alt_product_type' );

function get_alt_product_class( $php_classname, $product_type ) {
	return 'nab-alt-product' === $product_type
		? __NAMESPACE__ . '\Alternative_Product'
		: $php_classname;
}//end get_alt_product_class()
add_filter( 'woocommerce_product_class', __NAMESPACE__ . '\get_alt_product_class', 10, 2 );
