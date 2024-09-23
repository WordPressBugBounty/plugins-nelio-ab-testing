<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function is_legacy_alternative( $alternative ) {
	return (
		isset( $alternative['excerpt'] ) ||
		isset( $alternative['imageId'] ) ||
		isset( $alternative['imageUrl'] ) ||
		isset( $alternative['price'] )
	);
}//end is_legacy_alternative()


function update_legacy_experiment( $experiment ) {
	if ( $experiment->get_type() !== 'nab/wc-product' ) {
		return;
	}//end if

	$alternatives = $experiment->get_alternatives();
	$control      = $alternatives[0];
	$alternatives = array_slice( $alternatives, 1 );

	if ( empty( $alternatives[0] ) || ! is_legacy_alternative( $alternatives[0]['attributes'] ) ) {
		return;
	}//end if

	foreach ( $alternatives as &$alternative ) {
		$alternative['attributes'] = create_alternative_content(
			$alternative['attributes'],
			$control['attributes'],
			$experiment->get_id()
		);

		$attrs   = $alternative['attributes'];
		$post_id = $alternative['attributes']['postId'];

		if ( ! empty( $attrs['name'] ) ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $attrs['name'],
				)
			);
		}//end if

		if ( ! empty( $attrs['excerpt'] ) ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_excerpt' => $attrs['excerpt'],
				)
			);
		}//end if

		if ( ! empty( absint( $attrs['imageId'] ) ) ) {
			update_post_meta( $post_id, '_thumbnail_id', absint( $attrs['imageId'] ) );
		}//end if

		if ( ! empty( $attrs['price'] ) ) {
			update_post_meta( $post_id, '_regular_price', $attrs['price'] );
			update_post_meta( $post_id, '_sale_price', '' );
		}//end if

		// Remove old metas.
		$alternative['attributes'] = array(
			'name'   => $attrs['name'],
			'postId' => $attrs['postId'],
		);
	}//end foreach

	remove_action( 'nab_save_experiment', __NAMESPACE__ . '\update_legacy_experiment' );
	$alternatives = array_merge( array( $control ), $alternatives );
	$experiment->set_alternatives( $alternatives );
	$experiment->save();
	add_action( 'nab_save_experiment', __NAMESPACE__ . '\update_legacy_experiment' );
}//end update_legacy_experiment()
add_action( 'nab_save_experiment', __NAMESPACE__ . '\update_legacy_experiment' );


function duplicate_legacy_alternative( $alternative ) {
	if ( ! is_legacy_alternative( $alternative ) ) {
		return $alternative;
	}//end if

	if ( ! isset( $alternative['postId'] ) ) {
		return $alternative;
	}//end if

	wp_delete_post( $alternative['postId'] );
	unset( $alternative['postId'] );
	return $alternative;
}//end duplicate_legacy_alternative()
add_filter( 'nab_nab/wc-product_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_legacy_alternative', 11 );


function load_legacy_alternative( $alternative, $control ) {

	if ( ! is_legacy_alternative( $alternative ) ) {
		return;
	}//end if

	$control_id = $control['postId'];

	add_filter(
		'nab_enable_custom_woocommerce_hooks',
		function( $enabled, $product_id ) use ( $control_id ) {
			return $enabled || $product_id === $control_id;
		},
		10,
		2
	);


	if ( ! empty( $alternative['name'] ) ) {
		add_nab_filter(
			'woocommerce_product_name',
			function( $name, $product_id ) use ( &$alternative, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $name;
				}//end if
				return $alternative['name'];
			},
			1,
			2
		);
	}//end if


	if ( ! empty( $alternative['excerpt'] ) ) {
		add_nab_filter(
			'woocommerce_product_short_description',
			function( $short_description, $product_id ) use ( &$alternative, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $short_description;
				}//end if
				return wc_format_content( $alternative['excerpt'] );
			},
			1,
			2
		);
	}//end if


	if ( ! empty( $alternative['imageId'] ) ) {
		add_nab_filter(
			'woocommerce_product_image_id',
			function( $image_id, $product_id ) use ( &$alternative, $control_id ) {
				if ( $product_id !== $control_id ) {
					return $image_id;
				}//end if
				return $alternative['imageId'];
			},
			1,
			2
		);
	}//end if


	if ( ! empty( $alternative['price'] ) ) {
		$replace_price = function( $price, $product_id ) use ( &$alternative, $control_id ) {
			if ( $product_id !== $control_id ) {
				return $price;
			}//end if
			return $alternative['price'];
		};
		add_nab_filter( 'woocommerce_product_regular_price', $replace_price, 1, 2 );
		add_nab_filter( 'woocommerce_product_sale_price', $replace_price, 1, 2 );
	}//end if

}//end load_legacy_alternative()
add_action( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\load_legacy_alternative', 11, 2 );
add_action( 'nab_nab/wc-product_preview_alternative', __NAMESPACE__ . '\load_legacy_alternative', 11, 2 );


function apply_legacy_alternative( $applied, $alternative, $control ) {
	if ( ! is_legacy_alternative( $alternative ) ) {
		return $applied;
	}//end if

	$product = \wc_get_product( $control['postId'] );
	if ( empty( $product ) ) {
		return $applied;
	}//end if

	if ( ! empty( $alternative['name'] ) ) {
		$product->set_name( $alternative['name'] );
	}//end if

	if ( ! empty( $alternative['excerpt'] ) && method_exists( $product, 'set_short_description' ) ) {
		$product->set_short_description( $alternative['excerpt'] );
	}//end if

	if ( ! empty( absint( $alternative['imageId'] ) ) ) {
		$product->set_image_id( absint( $alternative['imageId'] ) );
	}//end if

	if ( ! empty( $alternative['price'] ) ) {
		$product->set_regular_price( $alternative['price'] );
	}//end if

	$product->save();
	return true;
}//end apply_legacy_alternative()
add_filter( 'nab_nab/wc-product_apply_alternative', __NAMESPACE__ . '\apply_legacy_alternative', 11, 3 );
