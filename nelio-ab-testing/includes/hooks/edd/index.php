<?php
/**
 * This file defines hooks to test Easy Digital Downloads stuff.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @since      6.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'EDD' ) ) {
			return;
		}//end if

		require_once __DIR__ . '/helpers/index.php';
		require_once __DIR__ . '/compat/index.php';
		require_once __DIR__ . '/conversion-actions/index.php';
	},
	5
);
