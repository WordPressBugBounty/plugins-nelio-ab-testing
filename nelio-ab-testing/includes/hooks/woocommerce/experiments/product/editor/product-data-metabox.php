<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_meta_box;
use function wc_get_product;
use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\is_variable_product;

/**
 * Callback to add product data meta box.
 *
 * @return void
 */
function add_product_data_metabox() {
	$post_id = get_the_ID();
	/** @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product|null */
	$product = wc_get_product( $post_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return;
	}

	// Remove original metabox.
	remove_meta_box( 'woocommerce-product-data', 'product', 'normal' );

	// Maybe add new one.
	$experiment_id = $product->get_experiment_id();
	$experiment    = nab_get_experiment( $experiment_id );
	if ( ! is_wp_error( $experiment ) && 'nab/wc-product' === $experiment->get_type() ) {
		$control     = $experiment->get_alternative( 'control' );
		$control     = $control['attributes'];
		$ori_product = wc_get_product( $control['postId'] ?? 0 );
		if ( empty( $ori_product ) ) {
			return;
		}
		$active = empty( $control['disablePriceTesting'] ) || is_variable_product( $ori_product );
		if ( ! $active ) {
			return;
		}
	}

	add_meta_box(
		'product',
		__( 'Product data', 'nelio-ab-testing' ),
		__NAMESPACE__ . '\render_product_data_metabox',
		'product',
		'normal',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_product_data_metabox', 999 );

/**
 * Callback to render product data meta box.
 *
 * @param \WP_Post $post Post.
 *
 * @return void
 */
function render_product_data_metabox( $post ) {
	$product_id = $post->ID;
	$original   = get_original_product( $product_id );
	if ( empty( $original ) ) {
		echo esc_html_x( 'Something went wrong. Tested product could not be found.', 'text', 'nelio-ab-testing' );
		return;
	}

	wp_nonce_field( "nab_save_product_data_{$product_id}", 'nab_product_data_nonce' );
	echo '<div id="nab-product-data-root"></div>';

	if ( ! is_variable_product( $original ) ) {
		$settings = array(
			'type'          => 'regular',
			'originalPrice' => $original->get_regular_price(),
			'regularPrice'  => get_post_meta( $product_id, '_regular_price', true ),
			'salePrice'     => get_post_meta( $product_id, '_sale_price', true ),
		);
	} else {
		$variation_data = get_post_meta( $product_id, '_nab_variation_data', true );
		if ( ! is_array( $variation_data ) ) {
			$variation_data = array();
		}
		$control  = get_control_attributes( $product_id );
		$settings = array(
			'type'                  => 'variable',
			'isPriceTestingEnabled' => empty( $control['disablePriceTesting'] ),
			'variations'            => array_map(
				function ( $wc_variation ) use ( &$variation_data ) {
					$variation = $wc_variation->get_id();
					$variation = isset( $variation_data[ $variation ] ) ? $variation_data[ $variation ] : array();
					$variation = wp_parse_args(
						$variation,
						array(
							'imageId'      => 0,
							'regularPrice' => '',
							'salePrice'    => '',
							'description'  => '',
						)
					);
					return array(
						'id'            => $wc_variation->get_id(),
						'name'          => $wc_variation->get_name(),
						'imageId'       => absint( $variation['imageId'] ),
						'originalPrice' => $wc_variation->get_regular_price(),
						'regularPrice'  => $variation['regularPrice'],
						'salePrice'     => $variation['salePrice'],
						'description'   => $variation['description'],
					);
				},
				array_filter( array_map( 'wc_get_product', $original->get_children() ) )
			),
		);
	}

	printf(
		'<script type="text/javascript">nab.initProductDataMetabox( %s );</script>',
		wp_json_encode( $settings )
	);
}

/**
 * Callback to save product data.
 *
 * @param int $post_id Post ID.
 *
 * @return void
 */
function save_product_data( $post_id ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['nab_product_data_nonce'] ) ) {
		return;
	}

	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	$alt_product = wc_get_product( $post_id );
	if ( empty( $alt_product ) || 'nab-alt-product' !== $alt_product->get_type() ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['nab_product_data_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, "nab_save_product_data_{$post_id}" ) ) {
		return;
	}

	$props = array();
	if ( isset( $_POST['nab_regular_price'] ) && is_string( $_POST['nab_regular_price'] ) ) {
		$props['regular_price'] = sanitize_text_field( wp_unslash( $_POST['nab_regular_price'] ) );
	}

	if ( isset( $_POST['nab_sale_price'] ) && is_string( $_POST['nab_sale_price'] ) ) {
		$props['sale_price'] = sanitize_text_field( wp_unslash( $_POST['nab_sale_price'] ) );
	}

	if ( ! empty( $props ) ) {
		$alt_product->set_props( $props );
		$alt_product->save();
	}

	$ori_product = get_original_product( $post_id );
	// @phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$variation_data = wp_unslash( $_POST['nab_variation_data'] ?? array() );
	$variation_data = is_array( $variation_data ) ? $variation_data : array();
	if ( ! empty( $ori_product ) && ! empty( $variation_data ) ) {
		$children       = $ori_product->get_children();
		$variation_data = array_map(
			function ( $id, $values ) use ( &$children ) {
				/** @var int           $id     */
				/** @var array<string> $values */

				if ( ! in_array( $id, $children, true ) ) {
					return false;
				}

				return array(
					'id'           => $id,
					'imageId'      => isset( $values['imageId'] ) ? absint( $values['imageId'] ) : 0,
					'regularPrice' => isset( $values['regularPrice'] ) ? sanitize_text_field( $values['regularPrice'] ) : '',
					'salePrice'    => isset( $values['salePrice'] ) ? sanitize_text_field( $values['salePrice'] ) : '',
					'description'  => isset( $values['description'] ) ? sanitize_textarea_field( $values['description'] ) : '',
				);
			},
			array_map( fn( $k ) => absint( $k ), array_keys( $variation_data ) ),
			array_values( $variation_data )
		);
		$variation_data = array_filter( $variation_data );
		$variation_data = array_combine( array_map( fn( $vd ) => $vd['id'], $variation_data ), $variation_data );
		update_post_meta( $post_id, '_nab_variation_data', $variation_data );
	}
}
add_action( 'save_post', __NAMESPACE__ . '\save_product_data' );

/**
 * Gets control attributes.
 *
 * @param int $alternative_id Alternative ID.
 *
 * @return array{}|TWC_Product_Control_Attributes
 */
function get_control_attributes( $alternative_id ) {
	/** @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product|null */
	$product = wc_get_product( $alternative_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return array();
	}

	$experiment_id = $product->get_experiment_id();
	if ( empty( $experiment_id ) ) {
		return array();
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return array();
	}

	$control = $experiment->get_alternative( 'control' );
	return $control['attributes'];
}

/**
 * Gets original product.
 *
 * @param int $alternative_id Alternative ID.
 *
 * @return \WC_Product|false
 */
function get_original_product( $alternative_id ) {
	$control  = get_control_attributes( $alternative_id );
	$original = wc_get_product( $control['postId'] ?? 0 );
	return ! empty( $original ) ? $original : false;
}
