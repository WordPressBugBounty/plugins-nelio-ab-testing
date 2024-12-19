<?php
/**
 * This file defines hooks to test SureCart stuff.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @since      7.2.0
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'SureCart' ) ) {
			return;
		}//end if

		require_once __DIR__ . '/compat/index.php';
		require_once __DIR__ . '/conversion-actions/index.php';
	},
	5
);
