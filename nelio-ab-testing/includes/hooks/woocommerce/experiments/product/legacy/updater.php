<?php
namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to update legacy alternative on experiment save.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return void
 */
function update_legacy_alternatives( $experiment ) {
	if ( version_compare( '7.3.0', $experiment->get_version(), '<=' ) ) {
		return;
	}

	if ( $experiment->get_type() !== 'nab/wc-product' ) {
		return;
	}

	$alternatives = $experiment->get_alternatives();
	/** @var array{id:string,attributes:TWC_Product_Control_Attributes} */
	$control      = $alternatives[0];
	$alternatives = array_slice( $alternatives, 1 );

	$alternatives = array_map(
		function ( $alternative ) use ( &$experiment, $control ) {
			$legacy = get_legacy_product( $alternative['attributes'], $control['attributes']['postId'], $experiment->ID );
			if ( empty( $legacy ) ) {
				return $alternative;
			}

			$alternative['attributes'] = create_alternative_content(
				sanitize_alternative_attributes( array( 'name' => $alternative['attributes']['name'] ?? '' ) ),
				$control['attributes'],
				$experiment->get_id()
			);

			$new_post_id = $alternative['attributes']['postId'];
			$new_product = wc_get_product( $new_post_id );
			if ( empty( $new_product ) ) {
				return $alternative;
			}

			if ( ! empty( $legacy->get_name() ) ) {
				$new_product->set_name( $legacy->get_name() );
			}
			if ( ! empty( $legacy->get_short_description() ) ) {
				$new_product->set_short_description( $legacy->get_short_description() );
			}
			if ( $legacy->is_description_supported() && ! empty( $legacy->get_description() ) ) {
				$new_product->set_description( $legacy->get_description() );
			}

			if ( ! empty( $legacy->get_regular_price() ) ) {
				$new_product->set_regular_price( $legacy->get_regular_price() );
			}
			if ( $legacy->is_sale_price_supported() && ! empty( $legacy->get_sale_price() ) ) {
				$new_product->set_sale_price( $legacy->get_sale_price() );
			} else {
				$new_product->set_sale_price( '' );
			}

			if ( $legacy->has_variation_data() ) {
				update_post_meta( $new_product->get_id(), '_nab_variation_data', $legacy->get_variation_data() );
			}

			if ( ! empty( $legacy->get_image_id() ) ) {
				$new_product->set_image_id( $legacy->get_image_id() );
			}
			if ( $legacy->is_gallery_supported() && ! empty( $legacy->get_gallery_image_ids() ) ) {
				$new_product->set_gallery_image_ids( $legacy->get_gallery_image_ids() );
			}

			if ( ! empty( $legacy->get_id() ) ) {
				wp_delete_post( $legacy->get_id() );
			}

			$new_product->save();
			return $alternative;
		},
		$alternatives
	);

	$alternatives = array_merge( array( $control ), $alternatives );
	$experiment->set_alternatives( $alternatives );
}
add_action( 'nab_pre_save_experiment', __NAMESPACE__ . '\update_legacy_alternatives' );

/**
 * Returns legacy product, if any.
 *
 * @param TAttributes $alternative   Alternative.
 * @param int         $control_id    Control ID.
 * @param int         $experiment_id Experiment ID.
 *
 * @return IRunning_Alternative_Product|null
 */
function get_legacy_product( $alternative, $control_id, $experiment_id ) {
	if ( is_v1_alternative( $alternative ) ) {
		return new Running_Alternative_Product_V1( $alternative, $control_id, $experiment_id );
	}

	if ( is_v2_alternative( $alternative ) ) {
		return new Running_Alternative_Product_V2( $alternative, $control_id, $experiment_id );
	}

	return null;
}
