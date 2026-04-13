<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to enable page tests on WooCommerce’s shop page.
 *
 * @param bool                     $is_tested     Whether the page test is set as active.
 * @param int                      $post_id       Current post ID.
 * @param TPost_Control_Attributes $control       Control attributes.
 * @param int                      $experiment_id Experiment ID.
 *
 * @return bool
 */
function enable_page_tests_on_shop_page( $is_tested, $post_id, $control, $experiment_id ) {
	if ( $is_tested ) {
		return $is_tested;
	}

	$shop_page_id = function_exists( 'wc_get_page_id' ) ? absint( wc_get_page_id( 'shop' ) ) : 0;
	if ( empty( $shop_page_id ) ) {
		return $is_tested;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $is_tested;
	}

	$tested_ids = $experiment->get_tested_posts();
	return in_array( $post_id, $tested_ids, true );
}
add_filter( 'nab_is_tested_post_by_nab/page_experiment', __NAMESPACE__ . '\enable_page_tests_on_shop_page', 10, 4 );

/**
 * Callback to fix account URLs.
 *
 * @param list<string> $urls Alternative URLs.
 *
 * @return list<string>
 */
function fix_account_url( $urls ) {
	if ( ! is_singular() ) {
		return $urls;
	}

	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return $urls;
	}

	if ( get_the_ID() !== wc_get_page_id( 'myaccount' ) ) {
		return $urls;
	}

	$request_uri = sanitize_url( is_string( $_SERVER['REQUEST_URI'] ?? '' ) ? wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) : '' );
	return array( site_url( $request_uri ) );
}
add_filter( 'nab_alternative_urls', __NAMESPACE__ . '\fix_account_url' );
