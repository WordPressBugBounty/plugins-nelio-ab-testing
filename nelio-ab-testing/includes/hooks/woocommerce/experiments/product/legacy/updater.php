<?php
namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function update_legacy_alternatives( $experiment ) {
	if ( version_compare( '7.3.0', $experiment->get_version(), '<=' ) ) {
		return;
	}//end if

	if ( $experiment->get_type() !== 'nab/wc-product' ) {
		return;
	}//end if

	$alternatives = $experiment->get_alternatives();
	$control      = $alternatives[0];
	$alternatives = array_slice( $alternatives, 1 );

	$alternatives = array_map(
		function ( $alternative ) use ( &$experiment, $control ) {
			$legacy = get_legacy_product( $alternative['attributes'], $control['postId'], $experiment->ID );
			if ( empty( $legacy ) ) {
				return $alternative;
			}//end if

			$alternative['attributes'] = create_alternative_content(
				sanitize_alternative_attributes( array( 'name' => nab_array_get( $alternative, 'attributes.name', '' ) ) ),
				$control['attributes'],
				$experiment->get_id()
			);

			$new_post_id = nab_array_get( $alternative, 'attributes.postId', 0 );
			$new_product = wc_get_product( absint( $new_post_id ) );
			if ( empty( $new_product ) ) {
				return $alternative;
			}//end if

			if ( ! empty( $legacy->get_name() ) ) {
				$new_product->set_name( $legacy->get_name() );
			}//end if
			if ( ! empty( $legacy->get_short_description() ) ) {
				$new_product->set_short_description( $legacy->get_short_description() );
			}//end if
			if ( $legacy->is_description_supported() && ! empty( $legacy->get_description() ) ) {
				$new_product->set_description( $legacy->get_description() );
			}//end if

			if ( ! empty( $legacy->get_regular_price() ) ) {
				$new_product->set_regular_price( $legacy->get_regular_price() );
			}//end if
			if ( $legacy->is_sale_price_supported() && ! empty( $legacy->get_sale_price() ) ) {
				$new_product->set_sale_price( $legacy->get_sale_price() );
			} else {
				$new_product->set_sale_price( '' );
			}//end if

			if ( $legacy->has_variation_data() ) {
				update_post_meta( $new_product->get_id(), '_nab_variation_data', $legacy->get_variation_data() );
			}//end if

			if ( ! empty( $legacy->get_image_id() ) ) {
				$new_product->set_image_id( $legacy->get_image_id() );
			}//end if
			if ( $legacy->is_gallery_supported() && ! empty( $legacy->get_gallery_image_ids() ) ) {
				$new_product->set_gallery_image_ids( $legacy->get_gallery_image_ids() );
			}//end if

			if ( ! empty( $legacy->get_id() ) ) {
				wp_delete_post( $legacy->get_id() );
			}//end if

			$new_product->save();
			return $alternative;
		},
		$alternatives
	);

	$alternatives = array_merge( array( $control ), $alternatives );
	$experiment->set_alternatives( $alternatives );
}//end update_legacy_alternatives()
add_action( 'nab_pre_save_experiment', __NAMESPACE__ . '\update_legacy_alternatives' );

function get_legacy_product( $alternative, $control_id, $experiment_id ) {
	if ( is_v1_alternative( $alternative ) ) {
		return new Running_Alternative_Product_V1( $alternative, $control_id, $experiment_id );
	}//end if

	if ( is_v2_alternative( $alternative ) ) {
		return new Running_Alternative_Product_V2( $alternative, $control_id, $experiment_id );
	}//end if

	return null;
}//end get_legacy_product()
