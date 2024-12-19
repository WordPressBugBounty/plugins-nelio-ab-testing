<?php
/**
 * This file adds the account page and starts the render process.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that adds the account page.
 */
class Nelio_AB_Testing_Account_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			'',
			nab_is_subscribed()
				? _x( 'Account', 'text', 'nelio-ab-testing' )
				: _x( 'Premium', 'text', 'nelio-ab-testing' ),
			'manage_nab_account',
			'nelio-ab-testing-account'
		);
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "account", %s );
			} );
		} )();';

		$settings = array(
			'isSubscribed' => nab_is_subscribed(),
			'siteId'       => nab_get_site_id(),
		);

		wp_enqueue_style(
			'nab-account-page',
			nelioab()->plugin_url . '/assets/dist/css/account-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-account-page', 'account-page', true );

		wp_add_inline_script(
			'nab-account-page',
			sprintf(
				$script,
				wp_json_encode( $settings ) // phpcs:ignore
			)
		);
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		$title = $this->page_title;
		// phpcs:ignore
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-account-page.php';
	}//end display()
}//end class
