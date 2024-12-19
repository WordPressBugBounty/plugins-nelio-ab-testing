<?php

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

/**
 * Overwrite the feedback method in the WP_Upgrader_Skin
 * to suppress the normal feedback.
 */
class Nelio_AB_Testing_Quiet_Upgrader_Skin extends WP_Upgrader_Skin { // phpcs:ignore
	public function feedback( $value, ...$args ) {
		/* no output */
	}//end feedback()
}//end class
