<?php
namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function get_edit_link( $edit_link, $alternative ) {
	return function_exists( 'current_user_can' ) && current_user_can( 'edit_nab_experiments' ) && ! empty( $alternative['postId'] )
		? get_edit_post_link( $alternative['postId'], 'unescaped' )
		: $edit_link;
}//end get_edit_link()
add_filter( 'nab_nab/wc-product_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 2 );
