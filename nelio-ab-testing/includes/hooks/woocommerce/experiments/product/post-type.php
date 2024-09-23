<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function register_post_type;

function register_alt_product_type() {
	$labels = array(
		'name'                  => _x( 'Product Variant', 'text', 'nelio-ab-testing' ),
		'edit_item'             => _x( 'Edit product variant', 'command', 'nelio-ab-testing' ),
		'featured_image'        => __( 'Product image', 'woocommerce' ),
		'set_featured_image'    => __( 'Set product image', 'woocommerce' ),
		'remove_featured_image' => __( 'Remove product image', 'woocommerce' ),
		'use_featured_image'    => __( 'Use as product image', 'woocommerce' ),
		'insert_into_item'      => __( 'Insert into product', 'woocommerce' ),
		'uploaded_to_this_item' => __( 'Uploaded to this product', 'woocommerce' ),
	);

	$args = array(
		'labels'       => $labels,
		'can_export'   => true,
		'capabilities' => array(
			'create_posts'           => 'do_not_allow',
			// Meta capabilities.
			'edit_post'              => 'edit_nab_experiments',
			'read_post'              => 'do_not_allow',
			'delete_post'            => 'delete_nab_experiments',
			// Primitive capabilities used outside of map_meta_cap().
			'edit_posts'             => 'edit_nab_experiments',
			'edit_others_posts'      => 'edit_nab_experiments',
			'delete_posts'           => 'delete_nab_experiments',
			'publish_posts'          => 'do_not_allow',
			'read_private_posts'     => 'do_not_allow',
			// Primitive capabilities used within map_meta_cap().
			'read'                   => 'do_not_allow',
			'delete_private_posts'   => 'do_not_allow',
			'delete_published_posts' => 'do_not_allow',
			'delete_others_posts'    => 'delete_nab_experiments',
			'edit_private_posts'     => 'do_not_allow',
			'edit_published_posts'   => 'do_not_allow',
		),
		'hierarchical' => false,
		'map_meta_cap' => false,
		'public'       => false,
		'query_var'    => false,
		'rewrite'      => false,
		'show_in_menu' => false,
		'show_in_rest' => false,
		'show_ui'      => true,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
	);
	register_post_type( 'nab_alt_product', $args );
}//end register_alt_product_type()
add_action( 'init', __NAMESPACE__ . '\register_alt_product_type' );

function post_updated_messages( $messages ) {
	$messages['nab_alt_product'] = array(
		0  => '', // Unused.
		1  => _x( 'Product variant updated.', 'text', 'nelio-ab-testing' ),
		2  => __( 'Custom field updated.', 'woocommerce' ),
		3  => __( 'Custom field deleted.', 'woocommerce' ),
		4  => _x( 'Product variant updated.', 'text', 'nelio-ab-testing' ),
		5  => __( 'Revision restored.', 'woocommerce' ),
		6  => '', // Product published.
		7  => _x( 'Product variant saved.', 'text', 'nelio-ab-testing' ),
		8  => '', // Product submitted.
		9  => '', // Product scheduled.
		10 => _x( 'Product variant updated.', 'text', 'nelio-ab-testing' ),
	);
	return $messages;
}//end post_updated_messages()
add_filter( 'post_updated_messages', __NAMESPACE__ . '\post_updated_messages' );
