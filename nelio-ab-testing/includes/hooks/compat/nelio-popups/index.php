<?php

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'nelio-popups/nelio-popups.php' ) ) {
			return;
		}//end if

		require_once __DIR__ . '/utils.php';
		require_once __DIR__ . '/load.php';
		require_once __DIR__ . '/preview.php';
		require_once __DIR__ . '/tracking.php';
	}
);
