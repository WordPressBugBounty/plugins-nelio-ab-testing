<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Post_Helper;

use function add_filter;
use function wc_get_product;
use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\is_variable_product;

/**
 * Callback to copy control prices when enabling test with pricing deactivated.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return void
 */
function maybe_use_control_prices( $experiment ) {
	if ( 'nab/wc-product' !== $experiment->get_type() ) {
		return;
	}
	$control = $experiment->get_alternative( 'control' );
	$control = $control['attributes'];

	$is_price_testing_disabled = ! empty( $control['disablePriceTesting'] );

	$control_product = wc_get_product( $control['postId'] );
	if ( empty( $control_product ) ) {
		return;
	}

	$alternatives = $experiment->get_alternatives();
	$alternatives = array_map( fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ), $alternatives );
	unset( $alternatives[0] );
	$alternatives = array_values( $alternatives );

	// Regular Product.
	if ( ! is_variable_product( $control_product ) ) {
		foreach ( $alternatives as $alt_product_id ) {
			$alt_product = wc_get_product( $alt_product_id );
			if ( empty( $alt_product ) ) {
				continue;
			}

			// Only update prices if price testing is disabled.
			if ( $is_price_testing_disabled ) {
				$alt_product->set_regular_price( $control_product->get_regular_price() );
				$alt_product->set_sale_price( $control_product->get_sale_price() );
			}

			// Always save the alternative product.
			$alt_product->save();
		}
		return;
	}

	// Variable Product.
	$children               = $control_product->get_children();
	$children               = array_map( fn( $id ) => absint( $id ), $children );
	$control_variation_data = array();
	foreach ( $children as $product_id ) {
		$wc_variation = wc_get_product( $product_id );
		if ( empty( $wc_variation ) ) {
			continue;
		}

		$control_variation_data[ $product_id ] = array(
			'regularPrice' => $wc_variation->get_regular_price(),
			'salePrice'    => $wc_variation->get_sale_price(),
		);
	}

	foreach ( $alternatives as $alt_product_id ) {
		// Only update variation data if price testing is disabled.
		if ( $is_price_testing_disabled ) {
			$variation_data = get_post_meta( $alt_product_id, '_nab_variation_data', true );
			if ( is_array( $variation_data ) ) {
				foreach ( $variation_data as $variation_id => &$data ) {
					$data = array_merge( $data, $control_variation_data[ $variation_id ] ?? array() );
				}
				update_post_meta( $alt_product_id, '_nab_variation_data', $variation_data );
			}
		}

		// Always save the alternative product.
		$alt_product = wc_get_product( $alt_product_id );
		if ( ! empty( $alt_product ) ) {
			$alt_product->save();
		}
	}
}
add_action( 'nab_start_experiment', __NAMESPACE__ . '\maybe_use_control_prices' );
add_action( 'nab_resume_experiment', __NAMESPACE__ . '\maybe_use_control_prices' );

/**
 * Callback to get tested posts.
 *
 * @param list<int>                    $post_ids   Post IDs.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return list<int>
 */
function get_tested_posts( $post_ids, $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	$control = $control['attributes'];
	return array_filter( array( absint( $control['postId'] ) ) );
}
add_filter( 'nab_nab/wc-product_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );

/**
 * Callback to get taxonomies to overwrite.
 *
 * @param list<string> $taxs Taxonomies.
 * @param string       $type Post type.
 *
 * @return list<string>
 */
function get_taxonomies_to_overwrite( $taxs, $type ) {
	return 'product' === $type ? without( $taxs, 'product_type' ) : $taxs;
}
add_filter( 'nab_get_taxonomies_to_overwrite', __NAMESPACE__ . '\get_taxonomies_to_overwrite', 10, 2 );

/**
 * Callback to get metas to overwrite.
 *
 * @param list<string> $meta_keys Meta keys.
 * @param string       $type      Post type.
 *
 * @return list<string>
 */
function get_metas_to_overwrite( $meta_keys, $type ) {
	return 'product' === $type ? without( $meta_keys, '_product_attributes' ) : $meta_keys;
}
add_filter( 'nab_get_metas_to_overwrite', __NAMESPACE__ . '\get_metas_to_overwrite', 10, 2 );

/**
 * Callback to create alternative content.
 *
 * @param TAttributes                    $alternative Alternative.
 * @param TWC_Product_Control_Attributes $control Control.
 * @param int                            $experiment_id Experiment ID.
 *
 * @return TWC_Product_Alternative_Attributes
 */
function create_alternative_content( $alternative, $control, $experiment_id ) {
	$ori_product = wc_get_product( $control['postId'] );
	if ( empty( $ori_product ) ) {
		return array(
			'name'   => is_string( $alternative['name'] ?? '' ) ? ( $alternative['name'] ?? '' ) : '',
			'postId' => 0,
		);
	}

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

	$terms = wp_get_object_terms( $new_product->get_id(), 'product_type', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) ) {
		wp_remove_object_terms( $new_product->get_id(), $terms, 'product_type' );
	}
	wp_set_object_terms( $new_product->get_id(), 'nab-alt-product', 'product_type' );

	maybe_duplicate_variation_details_from_control( $ori_product, $new_product->get_id() );

	return array(
		'name'   => is_string( $alternative['name'] ?? '' ) ? ( $alternative['name'] ?? '' ) : '',
		'postId' => $new_product->get_id(),
	);
}
add_filter( 'nab_nab/wc-product_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

/**
 * Callback to duplicate alternative content.
 *
 * @param TWC_Product_Alternative_Attributes $new_alternative New alternative.
 * @param TWC_Product_Alternative_Attributes $old_alternative Old alternative.
 * @param int                                $experiment_id   Experiment ID.
 *
 * @return TWC_Product_Alternative_Attributes
 */
function duplicate_alternative_content( $new_alternative, $old_alternative, $experiment_id ) {
	$fake_control    = array(
		'postType' => 'product',
		'postId'   => $old_alternative['postId'],
	);
	$new_alternative = create_alternative_content( $new_alternative, $fake_control, $experiment_id );
	if ( empty( $new_alternative['postId'] ) ) {
		return $new_alternative;
	}

	$old_product = wc_get_product( $old_alternative['postId'] );
	if ( empty( $old_product ) ) {
		return $new_alternative;
	}

	if ( 'nab-alt-product' === $old_product->get_type() ) {
		maybe_duplicate_variation_details_from_alternative( $old_product, $new_alternative['postId'] );
	} else {
		maybe_duplicate_variation_details_from_control( $old_product, $new_alternative['postId'] );
	}

	return $new_alternative;
}
add_filter( 'nab_nab/wc-product_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative_content', 10, 3 );

/**
 * Callback to backup control.
 *
 * @param TAttributes                    $backup        Backup.
 * @param TWC_Product_Control_Attributes $control       Control.
 * @param int                            $experiment_id Experiment ID.
 *
 * @return TWC_Product_Alternative_Attributes
 */
function backup_control( $backup, $control, $experiment_id ) {
	$backup = create_alternative_content( $backup, $control, $experiment_id );
	if ( empty( $backup['postId'] ) ) {
		return $backup;
	}

	$ori_product = wc_get_product( $control['postId'] );
	if ( empty( $ori_product ) ) {
		return $backup;
	}

	maybe_duplicate_variation_details_from_control( $ori_product, $backup['postId'] );

	return $backup;
}
add_filter( 'nab_nab/wc-product_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );

/**
 * Callback to remove alternative content.
 *
 * @param TWC_Product_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function remove_alternative_content( $alternative ) {
	$product = wc_get_product( $alternative['postId'] );
	if ( $product ) {
		$product->delete( true );
	}
}
add_action( 'nab_nab/wc-product_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

/**
 * Callback to apply alternative.
 *
 * @param bool                               $applied     Applied.
 * @param TWC_Product_Alternative_Attributes $alternative Alternative.
 * @param TWC_Product_Control_Attributes     $control     Control.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative, $control ) {
	$control_id     = $control['postId'];
	$tested_product = wc_get_product( $control_id );
	if ( empty( $tested_product ) ) {
		return false;
	}

	$alternative_id   = $alternative['postId'];
	$alternative_post = get_post( $alternative_id );
	if ( empty( $alternative_post ) ) {
		return false;
	}

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control_id, $alternative_id );
	if ( is_variable_product( $tested_product ) ) {
		overwrite_nab_to_wc_variation_data( $alternative_id, $tested_product );
	}

	return true;
}
add_filter( 'nab_nab/wc-product_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

/**
 * Prevent SKU from being overwritten.
 *
 * @param list<string> $meta_keys Meta keys.
 *
 * @return list<string>
 */
function prevent_sku_overwrite( $meta_keys ) {
	return without( $meta_keys, '_sku' );
}
add_filter( 'nab_get_metas_to_overwrite', __NAMESPACE__ . '\prevent_sku_overwrite' );

/**
 * Callback to maybe duplicate variation details from control.
 *
 * @param \WC_Product $source_product    Source product.
 * @param int         $target_product_id Target product ID.
 *
 * @return void
 */
function maybe_duplicate_variation_details_from_control( $source_product, $target_product_id ) {
	if ( ! is_variable_product( $source_product ) ) {
		return;
	}

	$children       = $source_product->get_children();
	$children       = array_map( fn( $id ) => absint( $id ), $children );
	$variation_data = array();
	foreach ( $children as $product_id ) {
		$wc_variation = wc_get_product( $product_id );
		if ( empty( $wc_variation ) ) {
			continue;
		}

		$variation_data[ $product_id ] = array(
			'id'           => $wc_variation->get_id(),
			'imageId'      => $wc_variation->get_image_id(),
			'regularPrice' => $wc_variation->get_regular_price(),
			'salePrice'    => $wc_variation->get_sale_price(),
			'description'  => $wc_variation->get_description(),
		);
	}

	update_post_meta( $target_product_id, '_nab_variation_data', $variation_data );
}

/**
 * Callback to maybe duplicate variation details from alternative.
 *
 * @param \WC_Product $source_product    Source product.
 * @param int         $target_product_id Target product ID.
 *
 * @return void
 */
function maybe_duplicate_variation_details_from_alternative( $source_product, $target_product_id ) {
	$variation_data = get_post_meta( $source_product->get_id(), '_nab_variation_data', true );
	if ( empty( $variation_data ) ) {
		return;
	}
	update_post_meta( $target_product_id, '_nab_variation_data', $variation_data );
}

/**
 * Overwrites NAB variation data to WC variation data.
 *
 * @param int         $source_id      Source ID.
 * @param \WC_Product $target_product Target product.
 *
 * @return void
 */
function overwrite_nab_to_wc_variation_data( $source_id, $target_product ) {
	$children = $target_product->get_children();

	$variation_data = get_post_meta( $source_id, '_nab_variation_data', true );
	if ( ! is_array( $variation_data ) ) {
		return;
	}

	foreach ( $variation_data as $id => $attrs ) {
		if ( ! in_array( $id, $children, true ) ) {
			continue;
		}

		$variation = wc_get_product( $id );
		if ( empty( $variation ) ) {
			continue;
		}

		$variation->set_description( $attrs['description'] ?? '' );
		$variation->set_image_id( $attrs['imageId'] ?? 0 );
		$variation->set_regular_price( $attrs['regularPrice'] ?? '' );
		$variation->set_sale_price( $attrs['salePrice'] ?? '' );

		$variation->save();
	}
}

/**
 * Removes the value from the array.
 *
 * @template T
 *
 * @param list<T> $collection Collection.
 * @param T       $value      Value.
 *
 * @return list<T>
 */
function without( $collection, $value ) {
	return array_values( array_filter( $collection, fn( $v ) => $v !== $value ) );
}
