<?php
/**
 * This file adds the page to welcome nwe users and starts the render process.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that adds the welcome page.
 */
class Nelio_AB_Testing_Welcome_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Welcome', 'text', 'nelio-ab-testing' ),
			_x( 'Welcome', 'text', 'nelio-ab-testing' ),
			'manage_nab_account',
			'nelio-ab-testing'
		);
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "welcome", %s );
			} );
		} )();';

		global $wpdb;
		$old_account = get_option( 'nelioab_account_settings', false );
		$experiments = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID, post_status FROM $wpdb->posts WHERE post_type = %s AND post_status != %s",
				'nelioab_local_exp',
				'nelioab_deleted'
			)
		);

		$experiments = array_map(
			function ( $experiment ) {
				return array(
					'ID'     => absint( $experiment->ID ),
					'status' => str_replace( 'nelioab_', '', $experiment->post_status ),
				);
			},
			$experiments
		);

		$settings = array(
			'isOldSubscriber' => ! empty( $old_account ),
			'oldExperiments'  => $experiments,
		);

		wp_enqueue_style(
			'nab-welcome-page',
			nelioab()->plugin_url . '/assets/dist/css/welcome-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-welcome-page', 'welcome-page', true );

		wp_add_inline_script(
			'nab-welcome-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		$title = $this->page_title;
		// phpcs:ignore
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-welcome-page.php';
	}//end display()
}//end class
