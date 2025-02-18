<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

class Running_Control_Product implements IRunning_Alternative_Product {

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

	public function __construct( $control_id, $experiment_id ) {
		$this->control_id    = $control_id;
		$this->experiment_id = $experiment_id;
	}//end __construct()

	public function is_proper_woocommerce_product() {
		return false;
	}//end is_proper_woocommerce_product()

	public function should_use_control_value() {
		return true;
	}//end should_use_control_value()

	public function get_id() {
		return $this->control_id;
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
		return null;
	}//end get_post()

	public function get_name() {
		return '';
	}//end get_name()

	public function get_regular_price() {
		return '';
	}//end get_regular_price()

	public function is_sale_price_supported() {
		return false;
	}//end is_sale_price_supported()

	public function get_sale_price() {
		return '';
	}//end get_sale_price()

	public function is_description_supported() {
		return false;
	}//end is_description_supported()

	public function get_description() {
		return '';
	}//end get_description()

	public function get_short_description() {
		return '';
	}//end get_short_description()

	public function get_image_id() {
		return 0;
	}//end get_image_id()

	public function is_gallery_supported() {
		return false;
	}//end is_gallery_supported()

	public function get_gallery_image_ids() {
		return array();
	}//end get_gallery_image_ids()

	public function has_variation_data() {
		return false;
	}//end has_variation_data()

	public function get_variation_data() {
		return array();
	}//end get_variation_data()

	public function get_variation_field( $variation_id, $field, $default_value ) {
		return $default_value;
	}//end get_variation_field()
}//end class
