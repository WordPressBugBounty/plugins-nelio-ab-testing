<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_meta_box;
use function wc_get_product;
use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\is_variable_product;

function add_product_data_metabox() {
	$post_id = get_the_ID();
	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product $product
	 */
	$product = wc_get_product( $post_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return;
	}//end if

	// Remove original metabox.
	remove_meta_box( 'woocommerce-product-data', 'product', 'normal' );

	// Maybe add new one.
	$experiment_id = $product->get_experiment_id();
	$experiment    = nab_get_experiment( $experiment_id );
	if ( ! is_wp_error( $experiment ) && 'nab/wc-product' === $experiment->get_type() ) {
		$control     = nab_array_get( $experiment->get_alternative( 'control' ), 'attributes', array() );
		$ori_product = wc_get_product( $control['postId'] );
		$active      = empty( $control['disablePriceTesting'] ) || is_variable_product( $ori_product );
		if ( ! $active ) {
			return;
		}//end if
	}//end if

	add_meta_box(
		'product',
		__( 'Product data', 'woocommerce' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		__NAMESPACE__ . '\render_product_data_metabox',
		'product',
		'normal',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}//end add_product_data_metabox()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_product_data_metabox', 999 );


function render_product_data_metabox( $post ) {
	$product_id = $post->ID;
	$original   = get_original_product( $product_id );
	if ( empty( $original ) ) {
		echo esc_html_x( 'Something went wrong. Tested product could not be found.', 'text', 'nelio-ab-testing' );
		return;
	}//end if

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
		}//end if
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
	}//end if

	printf(
		'<script type="text/javascript">nab.initProductDataMetabox( %s );</script>',
		wp_json_encode( $settings )
	);
}//end render_product_data_metabox()


function save_product_data( $post_id ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}//end if

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}//end if

	if ( ! isset( $_POST['nab_product_data_nonce'] ) ) {
		return;
	}//end if

	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}//end if

	$alt_product = wc_get_product( $post_id );
	if ( empty( $alt_product ) || 'nab-alt-product' !== $alt_product->get_type() ) {
		return;
	}//end if

	$nonce = sanitize_text_field( wp_unslash( $_POST['nab_product_data_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, "nab_save_product_data_{$post_id}" ) ) {
		return;
	}//end if

	$props = array();
	if ( isset( $_POST['nab_regular_price'] ) ) {
		$props['regular_price'] = sanitize_text_field( wp_unslash( $_POST['nab_regular_price'] ) );
	}//end if

	if ( isset( $_POST['nab_sale_price'] ) ) {
		$props['sale_price'] = sanitize_text_field( wp_unslash( $_POST['nab_sale_price'] ) );
	}//end if

	if ( ! empty( $props ) ) {
		$alt_product->set_props( $props );
		$alt_product->save();
	}//end if

	$ori_product    = get_original_product( $post_id );
	$variation_data = nab_array_get( $_POST, 'nab_variation_data', array() );
	$variation_data = is_array( $variation_data ) ? $variation_data : array();
	if ( ! empty( $ori_product ) && ! empty( $variation_data ) ) {
		$children       = $ori_product->get_children();
		$variation_data = array_map(
			function ( $id, $values ) use ( &$children ) {
				$id = absint( $id );
				if ( ! in_array( $id, $children, true ) ) {
					return false;
				}//end if
				return array(
					'id'           => $id,
					'imageId'      => isset( $values['imageId'] ) ? absint( $values['imageId'] ) : 0,
					'regularPrice' => isset( $values['regularPrice'] ) ? sanitize_text_field( $values['regularPrice'] ) : '',
					'salePrice'    => isset( $values['salePrice'] ) ? sanitize_text_field( $values['salePrice'] ) : '',
					'description'  => isset( $values['description'] ) ? sanitize_textarea_field( $values['description'] ) : '',
				);
			},
			array_keys( $variation_data ),
			array_values( $variation_data )
		);
		$variation_data = array_filter( $variation_data );
		$variation_data = array_combine( wp_list_pluck( $variation_data, 'id' ), $variation_data );
		update_post_meta( $post_id, '_nab_variation_data', $variation_data );
	}//end if
}//end save_product_data()
add_action( 'save_post', __NAMESPACE__ . '\save_product_data' );


function get_control_attributes( $alternative_id ) {
	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product $product
	 */
	$product = wc_get_product( $alternative_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return array();
	}//end if

	$experiment_id = $product->get_experiment_id();
	if ( empty( $experiment_id ) ) {
		return array();
	}//end if

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return array();
	}//end if

	$control = $experiment->get_alternative( 'control' );
	return nab_array_get( $control, 'attributes', array() );
}//end get_control_attributes()

function get_original_product( $alternative_id ) {
	$control  = get_control_attributes( $alternative_id );
	$original = wc_get_product( nab_array_get( $control, 'postId' ) );
	return empty( $original ) ? false : $original;
}//end get_original_product()
