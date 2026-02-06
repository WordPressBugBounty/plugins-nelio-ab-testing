<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

class Running_Alternative_Product implements IRunning_Alternative_Product {

	/**
	 * Original product.
	 *
	 * @var \WC_Product|false|null $control
	 */
	private $control = null;

	/**
	 * Original product ID.
	 *
	 * @var int
	 */
	private $control_id = 0;

	/**
	 * Experiment ID.
	 *
	 * @var int
	 */
	private $experiment_id = 0;

	/**
	 * Alternative.
	 *
	 * @var TWC_Product_Alternative_Attributes
	 */
	private $alternative = array(
		'name'   => '',
		'postId' => 0,
	);

	/**
	 * Alternative product.
	 *
	 * @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product|null
	 */
	private $product = null;

	/**
	 * Current post.
	 *
	 * @var \WP_Post|null
	 */
	private $post = null;

	/**
	 * Variantion data.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $variation_data = array();

	/**
	 * Creates new instance.
	 *
	 * @param TWC_Product_Alternative_Attributes $alternative   Alternative.
	 * @param int                                $control_id    Control ID.
	 * @param int                                $experiment_id Experiment ID.
	 *
	 * @return void
	 */
	public function __construct( $alternative, $control_id, $experiment_id ) {
		$this->alternative   = $alternative;
		$this->control_id    = $control_id;
		$this->experiment_id = $experiment_id;
		$this->post          = get_post( $this->alternative['postId'] );
		$this->load_variation_data( $this->alternative['postId'] );
		$this->load_proper_woocommerce_product( $this->alternative['postId'] );
	}

	public function is_proper_woocommerce_product() {
		return true;
	}

	public function should_use_control_value() {
		return empty( $this->product );
	}

	public function get_id() {
		return empty( $this->post ) ? 0 : $this->post->ID;
	}

	public function get_control() {
		if ( is_null( $this->control ) ) {
			$this->control = wc_get_product( $this->get_control_id() );
			$this->control = ! empty( $this->control ) ? $this->control : false;
		}

		if ( empty( $this->control ) ) {
			return null;
		}

		return $this->control;
	}

	public function get_control_id() {
		return $this->control_id;
	}

	public function get_experiment_id() {
		return $this->experiment_id;
	}

	public function get_post() {
		return $this->post;
	}

	public function get_name() {
		return ! empty( $this->product ) ? $this->product->get_name() : '';
	}

	public function get_regular_price() {
		/** @var string */
		return get_post_meta( $this->get_id(), '_regular_price', true );
	}

	public function is_sale_price_supported() {
		return true;
	}

	public function get_sale_price() {
		/** @var string */
		return get_post_meta( $this->get_id(), '_sale_price', true );
	}

	public function is_description_supported() {
		return true;
	}

	public function get_description() {
		return ! empty( $this->product ) ? $this->product->get_description() : '';
	}

	public function get_short_description() {
		return ! empty( $this->product ) ? $this->product->get_short_description() : '';
	}

	public function get_image_id() {
		return absint( ! empty( $this->product ) ? $this->product->get_image_id() : '' );
	}

	public function is_gallery_supported() {
		return true;
	}

	public function get_gallery_image_ids() {
		$image_ids = ! empty( $this->product ) ? $this->product->get_gallery_image_ids() : array();
		$image_ids = array_map( fn( $id ) => absint( $id ), $image_ids );
		return array_values( array_filter( $image_ids ) );
	}

	public function has_variation_data() {
		return ! empty( $this->variation_data );
	}

	public function get_variation_data() {
		return $this->variation_data;
	}

	public function get_variation_field( $variation_id, $field, $default_value ) {
		$data = isset( $this->variation_data[ $variation_id ] ) ? $this->variation_data[ $variation_id ] : array();
		return ! empty( $data[ $field ] ) ? $data[ $field ] : $default_value;
	}

	/**
	 * Loads variation data.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function load_variation_data( $post_id ) {
		$variation_data = get_post_meta( $post_id, '_nab_variation_data', true );
		if ( empty( $variation_data ) ) {
			$variation_data = array();
		}
		$this->variation_data = $variation_data;
	}

	/**
	 * Loads proper product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return void
	 */
	private function load_proper_woocommerce_product( $product_id ) {
		if ( empty( $this->post ) ) {
			return;
		}

		if ( 'product' === $this->post->post_type ) {
			if ( did_action( 'init' ) && function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( $product_id );
				/** @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product|null */
				$product       = ! empty( $product ) ? $product : null;
				$this->product = $product;
			} else {
				add_action(
					'init',
					function () use ( $product_id ) {
						if ( ! function_exists( 'wc_get_product' ) ) {
							return;
						}
						$product = wc_get_product( $product_id );
						/** @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product|null */
						$product       = ! empty( $product ) ? $product : null;
						$this->product = $product;
					}
				);
			}
		}
	}
}
