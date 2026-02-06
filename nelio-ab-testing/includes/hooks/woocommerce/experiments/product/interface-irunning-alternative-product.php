<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

interface IRunning_Alternative_Product {
	/**
	 * Whether the alternative product is implemented as a WooCommerce product or not.
	 *
	 * @return bool
	 */
	public function is_proper_woocommerce_product();

	/**
	 * Whether we should use control value or not.
	 *
	 * @return bool
	 */
	public function should_use_control_value();

	/**
	 * Gets ID.
	 *
	 * @return int
	 */
	public function get_id();

	/**
	 * Gets control.
	 *
	 * @return \WC_Product|null
	 */
	public function get_control();

	/**
	 * Gets control ID.
	 *
	 * @return int
	 */
	public function get_control_id();

	/**
	 * Gets experiment ID.
	 *
	 * @return int
	 */
	public function get_experiment_id();

	/**
	 * Gets post.
	 *
	 * @return \WP_Post|null
	 */
	public function get_post();

	/**
	 * Gets name.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Gets regular price.
	 *
	 * @return string
	 */
	public function get_regular_price();

	/**
	 * Whether sale price is supported or not.
	 *
	 * @return bool
	 */
	public function is_sale_price_supported();

	/**
	 * Gets sale price.
	 *
	 * @return string
	 */
	public function get_sale_price();

	/**
	 * Whether description is supported or not.
	 *
	 * @return bool
	 */
	public function is_description_supported();

	/**
	 * Gets description.
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Gets short description.
	 *
	 * @return string
	 */
	public function get_short_description();

	/**
	 * Gets image ID.
	 *
	 * @return int
	 */
	public function get_image_id();

	/**
	 * Whether alternative gallery is supported.
	 *
	 * @return bool
	 */
	public function is_gallery_supported();

	/**
	 * Gets gallery image IDs.
	 *
	 * @return list<int>
	 */
	public function get_gallery_image_ids();

	/**
	 * Whether it has variation data.
	 *
	 * @return bool
	 */
	public function has_variation_data();

	/**
	 * Gets variation data.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_variation_data();

	/**
	 * Returns the field value for the given variation. If said value is not said, it’ll return the default value.
	 *
	 * @template T
	 *
	 * @param int    $variation_id  Variation ID.
	 * @param string $field         Field name.
	 * @param T      $default_value Default value.
	 *
	 * @return T
	 */
	public function get_variation_field( $variation_id, $field, $default_value );
}
