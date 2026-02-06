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
		}

		add_filter( 'nab_is_nab/wc-bulk-sale_relevant_in_url', __NAMESPACE__ . '\is_experiment_relevant', 10, 3 );
		add_filter( 'nab_is_nab/wc-product_relevant_in_url', __NAMESPACE__ . '\is_experiment_relevant', 10, 3 );
		add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_script_to_load_alternative_in_ajax' );
		add_action( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
	}
);

/**
 * Callback to add hooks to load variable price summary.
 *
 * @param TWC_Product_Alternative_Attributes|TWC_Product_Control_Attributes $alternative Alternative.
 * @param TWC_Product_Control_Attributes                                    $control Control.
 *
 * @return void
 */
function load_alternative( $alternative, $control ) {

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];
	if ( $control_id === $alternative_id ) {
		return;
	}

	$alternative = get_post( $alternative_id );
	if ( empty( $alternative ) ) {
		return;
	}

	$variation_data = get_post_meta( $alternative_id, '_nab_variation_data', true );
	if ( empty( $variation_data ) ) {

		nab_add_filter(
			'woocommerce_product_regular_price',
			function ( $price, $product_id ) use ( &$alternative, $control_id ) {
				/** @var string $price      */
				/** @var int    $product_id */

				if ( $product_id !== $control_id ) {
					return $price;
				}

				/** @var string */
				$regular_price = get_post_meta( $alternative->ID, '_regular_price', true );
				$regular_price = empty( $regular_price ) ? $price : $regular_price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $regular_price;
				}
				return wcpbc_the_zone()->get_exchange_rate_price( (float) $regular_price );
			},
			99,
			2
		);

		nab_add_filter(
			'woocommerce_product_sale_price',
			function ( $price, $product_id, $regular_price ) use ( &$alternative, $control_id ) {
				/** @var string $price         */
				/** @var int    $product_id    */
				/** @var string $regular_price */

				if ( $product_id !== $control_id ) {
					return $price;
				}

				/** @var string */
				$sale_price = get_post_meta( $alternative->ID, '_sale_price', true );
				$sale_price = empty( $sale_price ) ? $regular_price : $sale_price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $sale_price;
				}
				return wcpbc_the_zone()->get_exchange_rate_price( (float) $sale_price );
			},
			99,
			3
		);

	} else {

		nab_add_filter(
			'woocommerce_variation_regular_price',
			function ( $price, $product_id, $variation_id ) use ( &$variation_data, $control_id ) {
				/** @var string $price        */
				/** @var int    $product_id   */
				/** @var int    $variation_id */

				if ( $product_id !== $control_id ) {
					return $price;
				}
				$data  = isset( $variation_data[ $variation_id ] ) ? $variation_data[ $variation_id ] : array();
				$price = ! empty( $data['regularPrice'] ) ? $data['regularPrice'] : $price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $price;
				}
				return wcpbc_the_zone()->get_exchange_rate_price( (float) $price );
			},
			99,
			3
		);

		nab_add_filter(
			'woocommerce_variation_sale_price',
			function ( $price, $product_id, $regular_price, $variation_id ) use ( &$variation_data, $control_id ) {
				/** @var string $price         */
				/** @var int    $product_id    */
				/** @var string $regular_price */
				/** @var int    $variation_id  */

				if ( $product_id !== $control_id ) {
					return $price;
				}
				$data  = isset( $variation_data[ $variation_id ] ) ? $variation_data[ $variation_id ] : array();
				$price = ! empty( $data['salePrice'] ) ? $data['salePrice'] : $regular_price;

				if ( ! wcpbc_the_zone() || get_woocommerce_currency() === wcpbc_get_base_currency() ) {
					return $price;
				}
				return wcpbc_the_zone()->get_exchange_rate_price( (float) $price );
			},
			99,
			4
		);

	}
}

/**
 * Callback to mark the experiment as relevant if there’s a request to `wc-ajax=wcpbc_get_location`.
 *
 * @param bool   $relevant      Relevant.
 * @param int    $experiment_id Experiment ID.
 * @param string $url           URL.
 *
 * @return bool
 */
function is_experiment_relevant( $relevant, $experiment_id, $url ) {
	if ( strpos( $url, 'wc-ajax=wcpbc_get_location' ) === false ) {
		return $relevant;
	}
	return true;
}

/**
 * Callback to enqueue script to load alternative content in AJAX request.
 *
 * @return void
 */
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
}
