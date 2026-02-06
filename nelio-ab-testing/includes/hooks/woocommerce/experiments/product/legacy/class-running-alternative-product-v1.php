<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

class Running_Alternative_Product_V1 implements IRunning_Alternative_Product {

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
	 * @var TWC_Product_Alternative_Attributes_V1
	 */
	private $alternative = array(
		'excerpt'  => '',
		'imageId'  => 0,
		'imageUrl' => '',
		'price'    => '',
	);

	/**
	 * Creates new instance.
	 *
	 * @param TWC_Product_Alternative_Attributes_V1 $alternative   Alternative.
	 * @param int                                   $control_id    Control ID.
	 * @param int                                   $experiment_id Experiment ID.
	 *
	 * @return void
	 */
	public function __construct( $alternative, $control_id, $experiment_id ) {
		$this->alternative   = $alternative;
		$this->control_id    = $control_id;
		$this->experiment_id = $experiment_id;
	}

	public function is_proper_woocommerce_product() {
		return false;
	}

	public function should_use_control_value() {
		return false;
	}

	public function get_id() {
		return 0;
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
		return null;
	}

	public function get_name() {
		return $this->alternative['name'] ?? '';
	}

	public function get_regular_price() {
		return $this->alternative['price'];
	}

	public function is_sale_price_supported() {
		return false;
	}

	public function get_sale_price() {
		return '';
	}

	public function is_description_supported() {
		return false;
	}

	public function get_description() {
		return '';
	}

	public function get_short_description() {
		return $this->alternative['excerpt'];
	}

	public function get_image_id() {
		return absint( $this->alternative['imageId'] );
	}

	public function is_gallery_supported() {
		return false;
	}

	public function get_gallery_image_ids() {
		return array();
	}

	public function has_variation_data() {
		return false;
	}

	public function get_variation_data() {
		return array();
	}

	public function get_variation_field( $variation_id, $field, $default_value ) {
		return $default_value;
	}
}
