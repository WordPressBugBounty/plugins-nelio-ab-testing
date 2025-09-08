<?php
/**
 * The plugin bootstrap file
 *
 * Plugin Name:       Nelio AB Testing
 * Plugin URI:        https://neliosoftware.com/testing/
 * Description:       Optimize your site based on data, not opinions. With this plugin, you will be able to perform AB testing (and more) on your WordPress site.
 * Version:           8.1.0
 *
 * Author:            Nelio Software
 * Author URI:        https://neliosoftware.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Requires at least: 6.7
 * Requires PHP:      7.4
 *
 * Text Domain:       nelio-ab-testing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

define( 'NELIO_AB_TESTING', true );
require untrailingslashit( __DIR__ ) . '/class-nelio-ab-testing.php';

/**
 * Returns the unique instance of Nelio A/B Testing’s main class.
 *
 * @return Nelio_AB_Testing unique instance of Nelio A/B Testing’s main class.
 *
 * @since @5.0.0
 */
function nelioab() {
	return Nelio_AB_Testing::instance();
}//end nelioab()

// Start plugin.
nelioab();
