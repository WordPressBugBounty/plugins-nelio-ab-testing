<?php
/**
 * This file contains the class for registering the plugin's recordings page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      6.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the plugin's recordings page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      6.4.0
 */
class Nelio_AB_Testing_Recordings_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Recordings', 'text', 'nelio-ab-testing' ),
			_x( 'Recordings', 'text', 'nelio-ab-testing' ),
			'edit_nab_experiments',
			'nelio-ab-testing-recordings'
		);
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "recordings", %s );
			} );
		} )();';

		$settings = array(
			'isSubscribed'        => nab_is_subscribed(),
			'isSubscribedToAddon' => nab_is_subscribed_to_addon( 'nsr-addon' ),
			'isPluginInstalled'   => nab_is_plugin_installed( 'nelio-session-recordings/nelio-session-recordings.php' ),
			'isPluginActive'      => is_plugin_active( 'nelio-session-recordings/nelio-session-recordings.php' ),
		);

		wp_enqueue_style(
			'nab-recordings-page',
			nelioab()->plugin_url . '/assets/dist/css/recordings-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-recordings-page', 'recordings-page', true );

		wp_add_inline_script(
			'nab-recordings-page',
			sprintf(
				$script,
				wp_json_encode( $settings ) // phpcs:ignore
			)
		);
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		// phpcs:ignore
		require_once nelioab()->plugin_path . '/admin/views/nelio-ab-testing-recordings-page.php';
	}//end display()
}//end class
