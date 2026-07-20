<?php

namespace Nelio_AB_Testing\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Removes WooCommerce products from the list of testable custom post types.
 *
 * @param list<string> $post_types Post types.
 *
 * @return list<string>
 */
function remove_products_from_testable_custom_post_types( $post_types ) {
	return array_values( array_diff( $post_types, array( 'elementor_library', 'e-floating-buttons' ) ) );
}
add_filter( 'nab_get_testable_custom_post_types', __NAMESPACE__ . '\remove_products_from_testable_custom_post_types' );
