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
		$control = nab_array_get( $experiment->get_alternative( 'control' ), 'attributes', array() );
		if ( ! empty( $control['disablePriceTesting'] ) ) {
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
		$settings = array(
			'type'       => 'variable',
			'variations' => array_map(
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


function render_variation_fields( $product_id, $variation ) {
	$varid = $variation->get_id();
	$value = function ( $value ) {
		printf( '"%s"', esc_attr( $value ) );
	};
	$id    = function ( $name ) use ( $varid, &$value ) {
		$value( "nab_product_variation_{$varid}_{$name}" );
	};
	$name  = function ( $attr ) use ( $varid, &$value ) {
		$value( "nab_product_variation[{$varid}][{$attr}]" );
	};
	?>
	<div class="nab-product-variation">
		<div class="nab-product-variation__title">
			<?php
			printf( '<strong>#%d</strong> %s', esc_html( $varid ), esc_html( implode( ',', $variation->get_attributes() ) ) );
			?>
		</div>
		<div class="nab-product-variation__attributes">
			<div class="nab-product-variation__image">
				<input
					type="text"
					name=<?php $name( 'image_id' ); ?>
					value=""
				/>
			</div>
			<div class="nab-product-variation__pricing">
				<input
					id=<?php $id( 'regular_price' ); ?>
					type="text"
					name=<?php $name( 'regular_price' ); ?>
					value=""
					placeholder=<?php $value( $variation->get_regular_price() ); ?>
				/>
				<input
					type="text"
					name=<?php $name( 'sale_price' ); ?>
					value=""
				/>
			</div>
		</div>
	</div>
	<?php
}//end render_variation_fields()


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


function get_original_product( $alternative_id ) {
	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product $product
	 */
	$product = wc_get_product( $alternative_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return false;
	}//end if

	$experiment_id = $product->get_experiment_id();
	if ( empty( $experiment_id ) ) {
		return false;
	}//end if

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return false;
	}//end if

	$original_id = $experiment->get_tested_post();
	if ( empty( $original_id ) ) {
		return false;
	}//end if

	$original = wc_get_product( $original_id );
	if ( empty( $original ) ) {
		return false;
	}//end if

	return $original;
}//end get_original_product()
