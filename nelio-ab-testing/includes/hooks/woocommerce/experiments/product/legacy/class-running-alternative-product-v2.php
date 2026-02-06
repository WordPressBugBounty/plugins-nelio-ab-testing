<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

class Running_Alternative_Product_V2 implements IRunning_Alternative_Product {

	/**
	 * Original product.
	 *
	 * @var \WC_Product|null|false
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
	 * @var TWC_Product_Alternative_Attributes_V2
	 */
	private $alternative = array(
		'name'   => '',
		'postId' => 0,
	);

	/**
	 * Post.
	 *
	 * @var \WP_Post|null
	 */
	private $post = null;

	/**
	 * Variation data.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $variation_data = array();

	/**
	 * Creates a new instance.
	 *
	 * @param TWC_Product_Alternative_Attributes_V2 $alternative Alternative.
	 * @param int                                   $control_id Control ID.
	 * @param int                                   $experiment_id Experiment ID.
	 *
	 * @return void
	 */
	public function __construct( $alternative, $control_id, $experiment_id ) {
		$this->alternative   = $alternative;
		$this->control_id    = $control_id;
		$this->experiment_id = $experiment_id;
		$this->post          = get_post( $this->alternative['postId'] );
		$this->load_variation_data( $this->alternative['postId'] );
	}

	public function is_proper_woocommerce_product() {
		return false;
	}

	public function should_use_control_value() {
		return false;
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
		return ! empty( $this->post ) ? $this->post->post_title : '';
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
		return ! empty( $this->post ) ? $this->post->post_content : '';
	}

	public function get_short_description() {
		return ! empty( $this->post ) ? $this->post->post_excerpt : '';
	}

	public function get_image_id() {
		return absint( get_post_meta( $this->get_id(), '_thumbnail_id', true ) );
	}

	public function is_gallery_supported() {
		return true;
	}

	public function get_gallery_image_ids() {
		/** @var string */
		$image_ids = get_post_meta( $this->get_id(), '_product_image_gallery', true );
		$image_ids = explode( ',', $image_ids );
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
}
