<?php
namespace Nelio_AB_Testing\WooCommerce\Helpers\Actions;

defined( 'ABSPATH' ) || exit;

function notify_alternative_loaded( $experiment_id ) {
	/**
	 * Fires when a WooCommerce alternative has run.
	 *
	 * @param int $experiment_id Experiment ID.
	 *
	 * @since 6.6.0
	 */
	do_action( 'nab_woocommerce_alternative_loaded', $experiment_id );
}//end notify_alternative_loaded()
