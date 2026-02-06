<?php
/**
 * This file contains the class for registering the plugin's settings page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the plugin's settings page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */
class Nelio_AB_Testing_Settings_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Settings', 'text', 'nelio-ab-testing' ),
			_x( 'Settings', 'text', 'nelio-ab-testing' ),
			'manage_nab_options',
			'nelio-ab-testing-settings'
		);
	}

	// @Implements
	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( empty( $screen ) || 'nelio-a-b-testing_page_nelio-ab-testing-settings' !== $screen->id ) {
			return;
		}

		$settings = Nelio_AB_Testing_Settings::instance();
		wp_enqueue_script( $settings->get_generic_script_name() );

		wp_enqueue_style(
			'nab-settings-page',
			nelioab()->plugin_url . '/assets/dist/css/settings-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-settings-page', 'settings-page', true );

		wp_add_inline_script( 'nab-settings-page', 'nab.initPage()' );
	}

	// @Implements
	public function display() {
		require_once nelioab()->plugin_path . '/admin/views/nelio-ab-testing-settings-page.php';
	}

	protected function is_help_tab_enabled() {
		return true;
	}
}
