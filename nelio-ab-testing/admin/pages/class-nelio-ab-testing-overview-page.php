<?php
/**
 * This file adds the overview page and starts the render process.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that shows summaries of the state of the running experiments.
 */
class Nelio_AB_Testing_Overview_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Overview', 'text', 'nelio-ab-testing' ),
			_x( 'Overview', 'text', 'nelio-ab-testing' ),
			'read_nab_results',
			'nelio-ab-testing-overview'
		);
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "overview", %s );
			} );
		} )();';

		$settings = array(
			'staging'      => nab_is_staging(),
			'isDeprecated' => get_option( 'nab_is_subscription_deprecated', false ),
			'experiments'  => $this->get_experiments_data( nab_get_running_experiments() ),
			'heatmaps'     => $this->get_experiments_data( nab_get_running_heatmaps() ),
			'subscription' => nab_get_subscription(),
		);

		wp_enqueue_style(
			'nab-overview-page',
			nelioab()->plugin_url . '/assets/dist/css/overview-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-overview-page', 'overview-page', true );

		wp_add_inline_script(
			'nab-overview-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {

		if ( isset( $_GET['experiment-debug'] ) ) { // phpcs:ignore
			$experiment_id = absint( $_GET['experiment-debug'] ); // phpcs:ignore
			$experiment    = nab_get_experiment( $experiment_id );
			// phpcs:ignore
			include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-experiment-debug.php';
			return;
		}//end if

		$title = $this->page_title;
		// phpcs:ignore
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-overview-page.php';
	}//end display()

	private function get_experiments_data( $experiments ) {

		return array_map(
			function ( $experiment ) {
				return array(
					'id'   => $experiment->get_id(),
					'type' => $experiment->get_type(),
					'name' => $experiment->get_name(),
				);
			},
			$experiments
		);
	}//end get_experiments_data()
}//end class
