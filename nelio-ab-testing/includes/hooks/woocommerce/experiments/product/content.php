<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function is_wp_error;
use function wc_get_product;
use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\is_variable_product;


function get_tested_element( $_, $control ) {
	return $control['postId'];
}//end get_tested_element()
add_filter( 'nab_nab/wc-product_get_tested_element', __NAMESPACE__ . '\get_tested_element', 10, 2 );


function create_alternative_content( $alternative, $control, $experiment_id ) {
	$alt_product_id = wp_insert_post( array( 'post_type' => 'nab_alt_product' ) );
	if ( is_wp_error( $alt_product_id ) ) {
		return $alternative;
	}//end if

	$alternative['postId'] = $alt_product_id;
	overwrite( $control['postId'], $alternative['postId'] );
	update_post_meta( $alternative['postId'], '_nab_experiment', $experiment_id );

	return $alternative;
}//end create_alternative_content()
add_filter( 'nab_nab/wc-product_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/wc-product_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );


function remove_alternative_content( $alternative ) {
	wp_delete_post( $alternative['postId'], true );
}//end remove_alternative_content()
add_action( 'nab_nab/wc-product_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );


// Control backup is equivalent to creating/removing variants.
add_filter( 'nab_nab/wc-product_backup_control', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_action( 'nab_remove_nab/wc-product_control_backup', __NAMESPACE__ . '\remove_alternative_content' );


function apply_alternative( $applied, $alternative, $control ) {
	return overwrite( $alternative['postId'], $control['postId'] );
}//end apply_alternative()
add_filter( 'nab_nab/wc-product_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );


function overwrite( $source_id, $target_id ) {
	$source_post       = get_post( $source_id, ARRAY_A );
	$target_post       = get_post( $target_id, ARRAY_A );
	$is_missing_a_post = empty( $source_post ) || empty( $target_post );
	if ( $is_missing_a_post ) {
		return false;
	}//end if

	$source_product       = wc_get_product( $source_id );
	$target_product       = wc_get_product( $target_id );
	$are_both_wc_products = ! empty( $source_product ) && ! empty( $target_product );
	if ( $are_both_wc_products ) {
		// This should never happen.
		return false;
	}//end if

	if ( ! empty( $source_product ) ) {
		return overwrite_wc_to_nab_product( $source_product, $target_id );
	}//end if

	if ( ! empty( $target_product ) ) {
		return overwrite_nab_to_wc_product( $source_post, $target_product );
	}//end if

	return overwrite_nab_to_nab_alt_products( $source_post, $target_id );
}//end overwrite()


function overwrite_nab_to_nab_alt_products( $source_post, $target_id ) {
	wp_update_post(
		array(
			'ID'           => $target_id,
			'post_title'   => $source_post['post_title'],
			'post_content' => $source_post['post_content'],
			'post_excerpt' => $source_post['post_excerpt'],
		)
	);

	$metas_to_copy = array(
		'_thumbnail_id',
		'_product_image_gallery',
		'_regular_price',
		'_sale_price',
		'_nab_variation_data',
	);
	foreach ( $metas_to_copy as $meta ) {
		$value = get_post_meta( $source_post['ID'], $meta, true );
		if ( empty( $value ) ) {
			delete_post_meta( $target_id, $meta );
		} else {
			update_post_meta( $target_id, $meta, $value );
		}//end if
	}//end foreach

	return true;
}//end overwrite_nab_to_nab_alt_products()


function overwrite_wc_to_nab_product( $source_product, $target_id ) {
	$source_post = get_post( $source_product->get_id(), ARRAY_A );
	overwrite_nab_to_nab_alt_products( $source_post, $target_id );
	maybe_overwrite_wc_to_nab_variation_data( $source_product, $target_id );
	return true;
}//end overwrite_wc_to_nab_product()


function overwrite_nab_to_wc_product( $source_post, $target_product ) {
	$target_product->set_name( $source_post['post_title'] );
	$target_product->set_description( $source_post['post_content'] );
	$target_product->set_short_description( $source_post['post_excerpt'] );

	$source_id = $source_post['ID'];
	$target_product->set_image_id( get_post_meta( $source_id, '_thumbnail_id', true ) );
	$target_product->set_gallery_image_ids( get_post_meta( $source_id, '_product_image_gallery', true ) );
	$target_product->set_regular_price( get_post_meta( $source_id, '_regular_price', true ) );
	$target_product->set_sale_price( get_post_meta( $source_id, '_sale_price', true ) );

	$target_product->save();

	maybe_overwrite_nab_to_wc_variation_data( $source_post['ID'], $target_product );
	return true;
}//end overwrite_nab_to_wc_product()


function maybe_overwrite_wc_to_nab_variation_data( $source_product, $target_id ) {
	if ( ! is_variable_product( $source_product ) ) {
		return;
	}//end if

	$children       = $source_product->get_children();
	$variation_data = array_map(
		function( $id ) {
			return array(
				'id'           => $id,
				'imageId'      => absint( get_post_meta( $id, '_thumbnail_id', true ) ),
				'regularPrice' => get_post_meta( $id, '_regular_price', true ),
				'salePrice'    => get_post_meta( $id, '_sale_price', true ),
				'description'  => get_post_meta( $id, '_variation_description', true ),
			);
		},
		$children
	);
	$variation_data = array_combine( wp_list_pluck( $variation_data, 'id' ), $variation_data );
	update_post_meta( $target_id, '_nab_variation_data', $variation_data );
}//end maybe_overwrite_wc_to_nab_variation_data()


function maybe_overwrite_nab_to_wc_variation_data( $source_id, $target_product ) {
	if ( ! is_variable_product( $target_product ) ) {
		return;
	}//end if

	$children       = $target_product->get_children();
	$variation_data = get_post_meta( $source_id, '_nab_variation_data', true );

	if ( ! is_array( $variation_data ) ) {
		return;
	}//end if

	foreach ( $variation_data as $id => $attrs ) {
		if ( ! in_array( $id, $children, true ) ) {
			continue;
		}//end if

		$variation = wc_get_product( $id );
		if ( empty( $variation ) ) {
			continue;
		}//end if

		$variation->set_description( $attrs['description'] );
		$variation->set_image_id( $attrs['imageId'] );
		$variation->set_regular_price( $attrs['regularPrice'] );
		$variation->set_sale_price( $attrs['salePrice'] );

		$variation->save();
	}//end foreach
}//end maybe_overwrite_nab_to_wc_variation_data()
