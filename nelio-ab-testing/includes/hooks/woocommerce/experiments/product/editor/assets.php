<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function get_current_screen;
use function wp_enqueue_media;
use function wp_enqueue_style;

function enqueue_product_gallery_assets() {
	$screen = get_current_screen();
	if ( 'product' !== $screen->id ) {
		return;
	}//end if

	$post_id = get_the_ID();
	$product = wc_get_product( $post_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return;
	}//end if

	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
	nab_enqueue_script_with_auto_deps( 'nab-product-experiment-management', 'product-experiment-management' );
	wp_enqueue_style(
		'nab-product-experiment-management',
		nelioab()->plugin_url . '/assets/dist/css/product-experiment-management.css',
		array( 'wp-admin', 'wp-components', 'nab-components', 'woocommerce_admin_styles' ),
		nelioab()->plugin_version
	);
}//end enqueue_product_gallery_assets()
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_product_gallery_assets' );
