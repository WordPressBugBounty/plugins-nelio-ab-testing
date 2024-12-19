<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use const WC_PLUGIN_FILE;
use const WC_VERSION;

use function add_filter;
use function add_action;
use function nab_get_running_experiments;
use function WC;

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $data ) {
		$items = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$items[] = $cart_item;
		}//end foreach
		$data['nab_cart_info'] = array(
			'items' => $items,
		);
		return $data;
	},
	99,
	1
);

function maybe_add_fragments_script() {
	$exps    = nab_get_running_experiments();
	$actions = array();
	foreach ( $exps as $exp ) {
		$goals = $exp->get_goals();
		foreach ( $goals as $goal ) {
			$actions = array_merge( $actions, $goal['conversionActions'] );
		}//end foreach
	}//end foreach

	$actions = wp_list_pluck( $actions, 'type' );
	if ( ! in_array( 'nab/wc-add-to-cart', $actions, true ) ) {
		return;
	}//end if

	if ( ! wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
		wp_register_script(
			'wc-cart-fragments',
			plugins_url( 'assets/js/frontend/cart-fragments.js', WC_PLUGIN_FILE ),
			array( 'jquery', 'js-cookie' ),
			WC_VERSION,
			true
		);
	}//end if
	wp_enqueue_script( 'wc-cart-fragments' );
}//end maybe_add_fragments_script()
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_add_fragments_script', 9999 );

function maybe_get_wc_shop_page_id( $page_id ) {
	if ( ! empty( $page_id ) ) {
		return $page_id;
	}//end if

	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return $page_id;
	}//end if

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return wc_get_page_id( 'shop' );
	}//end if

	return $page_id;
}//end maybe_get_wc_shop_page_id()
add_action( 'nab_get_queried_object_id', __NAMESPACE__ . '\maybe_get_wc_shop_page_id' );
