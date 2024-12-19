<?php
namespace Nelio_AB_Testing\WooCommerce\Compat\WooCommerce_Product_Based_On_Countries;

defined( 'ABSPATH' ) || exit;

use function wcpbc_the_zone;
use function wcpbc_get_base_currency;

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'WCPBC_PLUGIN_FILE' ) ) {
			return;
		}//end if

		add_filter( 'nab_is_nab/wc-bulk-sale_relevant_in_url', __NAMESPACE__ . '\is_experiment_relevant', 10, 3 );
		add_filter( 'nab_is_nab/wc-product_relevant_in_url', __NAMESPACE__ . '\is_experiment_relevant', 10, 3 );
		add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_script_to_load_alternative_in_ajax' );
		add_action( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
	}
);

function load_alternative( $alternative, $control ) {

	$control_id     = $control['postId'];
	$alternative_id = isset( $alternative['postId'] ) ? $alternative['postId'] : 0;
	if ( $control_id === $alternative_id ) {
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

	if ( empty( $variation_data ) ) {

		add_nab_filter(
			'woocommerce_product_regular_price',
			function ( $price, $product_id ) use ( &$alternative, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $price;
				}//end if

				$regular_price = get_post_meta( $alternative['ID'], '_regular_price', true );
				$regular_price = empty( $regular_price ) ? $price : $regular_price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $regular_price;
				}//end if
				return wcpbc_the_zone()->get_exchange_rate_price( $regular_price );
			},
			99,
			2
		);

		add_nab_filter(
			'woocommerce_product_sale_price',
			function ( $price, $product_id, $regular_price ) use ( &$alternative, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $price;
				}//end if

				$sale_price = get_post_meta( $alternative['ID'], '_sale_price', true );
				$sale_price = empty( $sale_price ) ? $regular_price : $sale_price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $sale_price;
				}//end if
				return wcpbc_the_zone()->get_exchange_rate_price( $sale_price );
			},
			99,
			3
		);

	} else {

		add_nab_filter(
			'woocommerce_variation_regular_price',
			function ( $price, $product_id, $variation_id ) use ( &$variation_data, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $price;
				}//end if
				$data  = isset( $variation_data[ $variation_id ] ) ? $variation_data[ $variation_id ] : array();
				$price = ! empty( $data['regularPrice'] ) ? $data['regularPrice'] : $price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $price;
				}//end if
				return wcpbc_the_zone()->get_exchange_rate_price( $price );
			},
			99,
			3
		);

		add_nab_filter(
			'woocommerce_variation_sale_price',
			function ( $price, $product_id, $regular_price, $variation_id ) use ( &$variation_data, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $price;
				}//end if
				$data  = isset( $variation_data[ $variation_id ] ) ? $variation_data[ $variation_id ] : array();
				$price = ! empty( $data['salePrice'] ) ? $data['salePrice'] : $regular_price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $price;
				}//end if
				return wcpbc_the_zone()->get_exchange_rate_price( $price );
			},
			99,
			4
		);

	}//end if
}//end load_alternative()

function is_experiment_relevant( $value, $experiment_id, $url ) {
	if ( strpos( $url, 'wc-ajax=wcpbc_get_location' ) === false ) {
		return $value;
	}//end if
	return true;
}//end is_experiment_relevant()

function enqueue_script_to_load_alternative_in_ajax() {
	$script = "
	( function() {
		if ( typeof jQuery === 'undefined' ) {
			return;
		}

		const urlParams = new URLSearchParams( window.location.search );
		const alternative = urlParams.get( 'nab' );
		if ( ! alternative || alternative === '0' ) {
			return;
		}

		jQuery.ajaxPrefilter( ( opts, oriOpts ) => {
			if ( ! opts.url.includes( 'wc-ajax=wcpbc_get_location' ) ) {
				return;
			}
			opts.url += '&nab=' + alternative;
		} );
	})();";

	wp_add_inline_script(
		'nelio-ab-testing-main',
		nab_minify_js( $script ),
		'before'
	);
}//end enqueue_script_to_load_alternative_in_ajax()
