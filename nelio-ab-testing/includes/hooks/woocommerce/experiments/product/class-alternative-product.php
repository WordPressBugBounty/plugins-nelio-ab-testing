<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use WC_Product;

class Alternative_Product extends WC_Product {
	public function get_type() {
		return 'nab-alt-product';
	}

	/**
	 * Sets experiment ID.
	 *
	 * @param int $experiment_id Experiment ID.
	 *
	 * @return void
	 */
	public function set_experiment_id( $experiment_id ) {
		update_post_meta( $this->get_id(), '_nab_experiment', $experiment_id );
	}

	/**
	 * Gets experiment ID.
	 *
	 * @return int
	 */
	public function get_experiment_id() {
		return absint( get_post_meta( $this->get_id(), '_nab_experiment', true ) );
	}

	/**
	 * Gets control ID.
	 *
	 * @return int
	 */
	public function get_control_id() {
		$experiment = nab_get_experiment( $this->get_experiment_id() );
		if ( is_wp_error( $experiment ) ) {
			return 0;
		}

		$control = $experiment->get_alternative( 'control' );
		return absint( $control['attributes']['postId'] ?? 0 );
	}
}
