<?php
/**
 * This file defines hooks to test WooCommerce stuff.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function() {
		if ( ! defined( 'WOOCOMMERCE_VERSION' ) ) {
			return;
		}//end if

		require_once dirname( __FILE__ ) . '/helpers/index.php';
		require_once dirname( __FILE__ ) . '/compat/index.php';

		require_once dirname( __FILE__ ) . '/experiments/index.php';
		require_once dirname( __FILE__ ) . '/conversion-actions/index.php';
	},
	5
);
