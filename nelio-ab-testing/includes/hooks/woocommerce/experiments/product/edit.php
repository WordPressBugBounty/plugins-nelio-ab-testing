<?php
namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                                      $edit_link      Edit link.
 * @param TWC_Product_Alternative_Attributes|TWC_Product_Control_Attributes $alternative    Alternative.
 *
 * @return string|false
 */
function get_edit_link( $edit_link, $alternative ) {
	if ( empty( $alternative['postId'] ) ) {
		return $edit_link;
	}

	if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_nab_experiments' ) ) {
		return $edit_link;
	}

	$edit_link = get_edit_post_link( $alternative['postId'], 'unescaped' );
	return ! empty( $edit_link ) ? $edit_link : false;
}
add_filter( 'nab_nab/wc-product_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );
