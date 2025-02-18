<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

interface IRunning_Alternative_Product {
	public function is_proper_woocommerce_product();
	public function should_use_control_value();
	public function get_id();
	public function get_control();
	public function get_control_id();
	public function get_experiment_id();
	public function get_post();
	public function get_name();
	public function get_regular_price();
	public function is_sale_price_supported();
	public function get_sale_price();
	public function is_description_supported();
	public function get_description();
	public function get_short_description();
	public function get_image_id();
	public function is_gallery_supported();
	public function get_gallery_image_ids();
	public function has_variation_data();
	public function get_variation_data();
	public function get_variation_field( $variation_id, $field, $default_value );
}//end interface
