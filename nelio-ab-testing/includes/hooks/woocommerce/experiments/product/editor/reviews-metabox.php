<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function remove_meta_box;

function remove_reviews_metabox() {
	$post_id = get_the_ID();
	$product = wc_get_product( $post_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return;
	}//end if
	remove_meta_box( 'commentsdiv', 'product', 'side' );
}//end remove_reviews_metabox()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\remove_reviews_metabox' );
