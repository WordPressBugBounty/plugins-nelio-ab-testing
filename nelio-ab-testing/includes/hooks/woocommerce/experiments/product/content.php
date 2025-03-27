<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Post_Helper;

use function add_filter;
use function wc_get_product;
use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\is_variable_product;


function get_tested_posts( $_, $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	$control = $control['attributes'];
	return array( $control['postId'] );
}//end get_tested_posts()
add_filter( 'nab_nab/wc-product_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );


add_filter(
	'nab_get_taxonomies_to_overwrite',
	fn( $taxs, $type ) =>
		'product' === $type ? without( $taxs, 'product_type' ) : $taxs,
	10,
	2
);


add_filter(
	'nab_get_metas_to_overwrite',
	fn( $meta_keys, $type ) =>
		'product' === $type ? without( $meta_keys, '_product_attributes' ) : $meta_keys,
	10,
	2
);


function create_alternative_content( $alternative, $control, $experiment_id ) {
	$ori_product = wc_get_product( $control['postId'] );
	if ( empty( $ori_product ) ) {
		return $alternative;
	}//end if

	$duplicator = new \WC_Admin_Duplicate_Product();

	// Duplicate product (but not its SKU).
	$sku = $ori_product->get_sku();
	$ori_product->set_sku( '' );
	$new_product = $duplicator->product_duplicate( $ori_product );
	$ori_product->set_sku( $sku );

	// Set proper attributes.
	$new_product = new Alternative_Product( $new_product->get_id() );
	$new_product->set_name( $ori_product->get_name() );
	$new_product->set_status( 'nab_hidden' );
	$new_product->set_slug( uniqid() );
	$new_product->set_experiment_id( $experiment_id );
	$new_product->save();

	maybe_duplicate_variation_details_from_control( $ori_product, $new_product->get_id() );

	$alternative['postId'] = $new_product->get_id();
	return $alternative;
}//end create_alternative_content()
add_filter( 'nab_nab/wc-product_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );


function duplicate_alternative_content( $new_alternative, $old_alternative, $experiment_id ) {
	$new_alternative = create_alternative_content( $new_alternative, $old_alternative, $experiment_id );
	if ( empty( $new_alternative['postId'] ) ) {
		return $new_alternative;
	}//end if

	$old_product = wc_get_product( $old_alternative['postId'] );
	if ( empty( $old_product ) ) {
		return $new_alternative;
	}//end if

	if ( 'nab-alt-product' === $old_product->get_type() ) {
		maybe_duplicate_variation_details_from_alternative( $old_product, $new_alternative['postId'] );
	} else {
		maybe_duplicate_variation_details_from_control( $old_product, $new_alternative['postId'] );
	}//end if

	return $new_alternative;
}//end duplicate_alternative_content()
add_filter( 'nab_nab/wc-product_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative_content', 10, 3 );


function backup_control( $alternative, $control, $experiment_id ) {
	$alternative = create_alternative_content( $alternative, $control, $experiment_id );
	if ( empty( $alternative['postId'] ) ) {
		return $alternative;
	}//end if

	$ori_product = wc_get_product( $control['postId'] );
	if ( empty( $ori_product ) ) {
		return $alternative;
	}//end if

	maybe_duplicate_variation_details_from_control( $ori_product, $alternative['postId'] );

	return $alternative;
}//end backup_control()
add_filter( 'nab_nab/wc-product_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );


function remove_alternative_content( $alternative ) {
	$product = wc_get_product( $alternative['postId'] );
	if ( $product ) {
		$product->delete( true );
	}//end if
}//end remove_alternative_content()
add_action( 'nab_nab/wc-product_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
add_action( 'nab_remove_nab/wc-product_control_backup', __NAMESPACE__ . '\remove_alternative_content' );


function apply_alternative( $applied, $alternative, $control ) {
	$control_id     = isset( $control['postId'] ) ? $control['postId'] : 0;
	$tested_product = wc_get_product( $control_id );
	if ( empty( $tested_product ) || is_wp_error( $tested_product ) ) {
		return false;
	}//end if

	$alternative_id   = isset( $alternative['postId'] ) ? $alternative['postId'] : 0;
	$alternative_post = get_post( $alternative_id );
	if ( empty( $alternative_post ) || is_wp_error( $alternative_post ) ) {
		return false;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control_id, $alternative_id );
	if ( is_variable_product( $tested_product ) ) {
		overwrite_nab_to_wc_variation_data( $alternative_id, $tested_product );
	}//end if

	return true;
}//end apply_alternative()
add_filter( 'nab_nab/wc-product_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );


function prevent_sku_overwrite( $meta_keys ) {
	return without( $meta_keys, '_sku' );
}//end prevent_sku_overwrite()
add_filter( 'nab_get_metas_to_overwrite', __NAMESPACE__ . '\prevent_sku_overwrite' );


function maybe_duplicate_variation_details_from_control( $source_product, $target_product_id ) {
	if ( ! is_variable_product( $source_product ) ) {
		return;
	}//end if

	$children       = $source_product->get_children();
	$variation_data = array();
	foreach ( $children as $product_id ) {
		$wc_variation = wc_get_product( $product_id );
		if ( empty( $wc_variation ) ) {
			continue;
		}//end if

		$variation_data[ $product_id ] = array(
			'id'           => $wc_variation->get_id(),
			'imageId'      => $wc_variation->get_image_id(),
			'regularPrice' => $wc_variation->get_regular_price(),
			'salePrice'    => $wc_variation->get_sale_price(),
			'description'  => $wc_variation->get_description(),
		);
	}//end foreach

	update_post_meta( $target_product_id, '_nab_variation_data', $variation_data );
}//end maybe_duplicate_variation_details_from_control()


function maybe_duplicate_variation_details_from_alternative( $source_product, $target_product_id ) {
	$variation_data = get_post_meta( $source_product->get_id(), '_nab_variation_data', true );
	if ( empty( $variation_data ) ) {
		return;
	}//end if
	update_post_meta( $target_product_id, '_nab_variation_data', $variation_data );
}//end maybe_duplicate_variation_details_from_alternative()


function overwrite_nab_to_wc_variation_data( $source_id, $target_product ) {
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
}//end overwrite_nab_to_wc_variation_data()


function without( $collection, $value ) {
	return array_values( array_filter( $collection, fn( $v ) => $v !== $value ) );
}//end without()
