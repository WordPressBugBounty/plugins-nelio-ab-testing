<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use const WC_PLUGIN_FILE;
use const WC_VERSION;

use function add_filter;
use function add_action;
use function nab_get_running_experiments;
use function WC;

/**
 * Callback to add to cart fragments.
 *
 * @param array<string,mixed> $data Data.
 *
 * @return array<string,mixed>
 */
function add_to_cart_fragments( $data ) {
	$items = array();
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$items[] = $cart_item;
	}
	$data['nab_cart_info'] = array(
		'items' => $items,
	);
	return $data;
}
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\add_to_cart_fragments', 99 );

/**
 * Callback to maybe add fragments script in frontend.
 *
 * @return void
 */
function maybe_add_fragments_script() {
	$exps    = nab_get_running_experiments();
	$actions = array();
	foreach ( $exps as $exp ) {
		$goals = $exp->get_goals();
		foreach ( $goals as $goal ) {
			$actions = array_merge( $actions, $goal['conversionActions'] );
		}
	}

	$actions = wp_list_pluck( $actions, 'type' );
	if ( ! in_array( 'nab/wc-add-to-cart', $actions, true ) ) {
		return;
	}

	if ( ! wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
		wp_register_script(
			'wc-cart-fragments',
			plugins_url( 'assets/js/frontend/cart-fragments.js', WC_PLUGIN_FILE ),
			array( 'jquery', 'js-cookie' ),
			WC_VERSION,
			true
		);
	}
	wp_enqueue_script( 'wc-cart-fragments' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_add_fragments_script', 9999 );

/**
 * Callback to maybe set nab’s queried object to WC shop page ID.
 *
 * @param int $page_id Page ID.
 *
 * @return int
 */
function maybe_get_wc_shop_page_id( $page_id ) {
	if ( ! empty( $page_id ) ) {
		return $page_id;
	}

	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return $page_id;
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return wc_get_page_id( 'shop' );
	}

	return $page_id;
}
add_filter( 'nab_get_queried_object_id', __NAMESPACE__ . '\maybe_get_wc_shop_page_id' );
