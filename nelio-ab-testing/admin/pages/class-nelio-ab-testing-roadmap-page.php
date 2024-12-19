<?php
/**
 * This file contains the class for registering the plugin's roadmap page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      6.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the plugin's roadmap page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      6.1.0
 */
class Nelio_AB_Testing_Roadmap_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Roadmap', 'text', 'nelio-ab-testing' ),
			_x( 'Roadmap', 'text', 'nelio-ab-testing' ),
			'edit_nab_experiments',
			'nelio-ab-testing-roadmap'
		);
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {
		$help_url = 'https://trello.com/b/4zBeOjTM';
		printf(
			'<meta http-equiv="refresh" content="0; url=%s" />',
			esc_url( $help_url )
		);
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		// Nothing to be done.
	}//end display()
}//end class
