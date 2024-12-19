<?php
/**
 * This file contains the class that registers the help menu item in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the help menu item in Nelio A/B Testing.
 */
class Nelio_AB_Testing_Help_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Help', 'text', 'nelio-ab-testing' ),
			_x( 'Help', 'text', 'nelio-ab-testing' ),
			'edit_nab_experiments',
			'nelio-ab-testing-help'
		);
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {
		$help_url = add_query_arg(
			array(
				'utm_source'   => 'nelio-ab-testing',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'support',
				'utm_content'  => 'overview-help',
			),
			_x( 'https://neliosoftware.com/testing/help/', 'text', 'nelio-ab-testing' )
		);
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
