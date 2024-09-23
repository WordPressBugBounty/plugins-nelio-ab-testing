<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function get_preview_link( $preview_link, $alternative, $control ) {
	$link = get_permalink( $control['postId'] );
	return ! empty( $link ) ? $link : $preview_link;
}//end get_preview_link()
add_filter( 'nab_nab/wc-product_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

add_action( 'nab_nab/wc-product_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );
