<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

class Running_Alternative_Product implements IRunning_Alternative_Product {

	/**
	 * .
	 *
	 * @var WC_Product|false $control
	 */
	private $control = false;

	/**
	 * .
	 *
	 * @var number $control_id
	 */
	private $control_id = 0;

	/**
	 * .
	 *
	 * @var number $experiment_id
	 */
	private $experiment_id = 0;

	/**
	 * .
	 *
	 * @var array $alternative
	 */
	private $alternative = 0;

	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Alternative_Product|null $product_id
	 */
	private $product = null;

	/**
	 * .
	 *
	 * @var \WP_Post|null $product_id
	 */
	private $post = null;

	/**
	 * .
	 *
	 * @var array $variation_data
	 */
	private $variation_data = array();

	public function __construct( $alternative, $control_id, $experiment_id ) {
		$this->alternative   = $alternative;
		$this->control_id    = $control_id;
		$this->experiment_id = $experiment_id;
		$this->post          = get_post( $this->alternative['postId'], ARRAY_A );
		$this->load_variation_data( $this->alternative['postId'] );
		$this->load_proper_woocommerce_product( $this->alternative['postId'] );
	}//end __construct()

	public function is_proper_woocommerce_product() {
		return true;
	}//end is_proper_woocommerce_product()

	public function should_use_control_value() {
		return empty( $this->product );
	}//end should_use_control_value()

	public function get_id() {
		return empty( $this->post ) ? 0 : $this->post['ID'];
	}//end get_id()

	public function get_control() {
		if ( empty( $this->control ) ) {
			$this->control = wc_get_product( $this->get_control_id() );
		}//end if
		return $this->control;
	}//end get_control()

	public function get_control_id() {
		return $this->control_id;
	}//end get_control_id()

	public function get_experiment_id() {
		return $this->experiment_id;
	}//end get_experiment_id()

	public function get_post() {
		return $this->post;
	}//end get_post()

	public function get_name() {
		return $this->product->get_name();
	}//end get_name()

	public function get_regular_price() {
		return get_post_meta( $this->get_id(), '_regular_price', true );
	}//end get_regular_price()

	public function is_sale_price_supported() {
		return true;
	}//end is_sale_price_supported()

	public function get_sale_price() {
		return get_post_meta( $this->get_id(), '_sale_price', true );
	}//end get_sale_price()

	public function is_description_supported() {
		return true;
	}//end is_description_supported()

	public function get_description() {
		return $this->product->get_description();
	}//end get_description()

	public function get_short_description() {
		return $this->product->get_short_description();
	}//end get_short_description()

	public function get_image_id() {
		return $this->product->get_image_id();
	}//end get_image_id()

	public function is_gallery_supported() {
		return true;
	}//end is_gallery_supported()

	public function get_gallery_image_ids() {
		return $this->product->get_gallery_image_ids();
	}//end get_gallery_image_ids()

	public function has_variation_data() {
		return ! empty( $this->variation_data );
	}//end has_variation_data()

	public function get_variation_data() {
		return $this->variation_data;
	}//end get_variation_data()

	public function get_variation_field( $variation_id, $field, $default_value ) {
		$data = isset( $this->variation_data[ $variation_id ] ) ? $this->variation_data[ $variation_id ] : array();
		return ! empty( $data[ $field ] ) ? $data[ $field ] : $default_value;
	}//end get_variation_field()

	private function load_variation_data( $post_id ) {
		$variation_data = get_post_meta( $post_id, '_nab_variation_data', true );
		if ( empty( $variation_data ) || ! is_array( $variation_data ) ) {
			$variation_data = array();
		}//end if
		$this->variation_data = $variation_data;
	}//end load_variation_data()

	private function load_proper_woocommerce_product( $product_id ) {
		if ( 'product' === $this->post['post_type'] ) {
			if ( did_action( 'init' ) && function_exists( 'wc_get_product' ) ) {
				$this->product = wc_get_product( $product_id );
			} else {
				add_action(
					'init',
					function () use ( &$product, $product_id ) {
						if ( ! function_exists( 'wc_get_product' ) ) {
							return;
						}//end if
						$this->product = wc_get_product( $product_id );
					}
				);
			}//end if
		}//end if
	}//end load_proper_woocommerce_product()
}//end class
