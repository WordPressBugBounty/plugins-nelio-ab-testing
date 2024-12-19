<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use WC_Product;

class Alternative_Product extends WC_Product {
	public function get_type() {
		return 'nab-alt-product';
	}//end get_type()

	public function set_experiment_id( $experiment_id ) {
		update_post_meta( $this->get_id(), '_nab_experiment', $experiment_id );
	}//end set_experiment_id()

	public function get_experiment_id() {
		return absint( get_post_meta( $this->get_id(), '_nab_experiment', true ) );
	}//end get_experiment_id()
}//end class
