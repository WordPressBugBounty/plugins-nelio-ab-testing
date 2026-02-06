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
