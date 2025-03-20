<?php
/**
 * This file contains the class that renders the results of an experiment page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that defines the results of an experiment page.
 */
class Nelio_AB_Testing_Results_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Results', 'text', 'nelio-ab-testing' ),
			_x( 'Tests', 'text', 'nelio-ab-testing' ),
			'read_nab_results',
			'nelio-ab-testing-experiment-view'
		);
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function init() {

		parent::init();
		add_action( 'admin_menu', array( $this, 'maybe_remove_this_page_from_the_menu' ), 999 );
		add_action( 'current_screen', array( $this, 'maybe_redirect_to_experiments_page' ) );
		add_action( 'current_screen', array( $this, 'die_if_params_are_invalid' ) );
		add_action( 'current_screen', array( $this, 'maybe_render_standalone_heatmap_page' ), 99 );
	}//end init()

	public function maybe_redirect_to_experiments_page() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		if ( ! $this->does_request_have_an_experiment() ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment' ) );
			exit;
		}//end if
	}//end maybe_redirect_to_experiments_page()

	public function maybe_remove_this_page_from_the_menu() {

		if ( ! $this->is_current_screen_this_page() ) {
			$this->remove_this_page_from_the_menu();
		} else {
			$this->remove_experiments_list_from_menu();
		}//end if
	}//end maybe_remove_this_page_from_the_menu()

	public function die_if_params_are_invalid() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		$experiment_id = absint( nab_array_get( $_GET, 'experiment', 0 ) ); // phpcs:ignore
		if ( 'nab_experiment' !== get_post_type( $experiment_id ) ) {
			wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
			return;
		}//end if

		$experiment = nab_get_experiment( $experiment_id );
		$status     = $experiment->get_status();
		if ( ! in_array( $status, array( 'running', 'finished' ), true ) ) {
			wp_die( esc_html_x( 'You’re not allowed to view this page.', 'user', 'nelio-ab-testing' ) );
			return;
		}//end if
	}//end die_if_params_are_invalid()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		wp_register_style(
			'nab-results-page',
			nelioab()->plugin_url . '/assets/dist/css/results-page.css',
			array( 'nab-components', 'nab-experiment-library' ),
			nelioab()->plugin_version
		);

		wp_register_style(
			'nab-heatmap-results-page',
			nelioab()->plugin_url . '/assets/dist/css/heatmap-results-page.css',
			array( 'nab-results-page' ),
			nelioab()->plugin_version
		);

		/**
		 * Fires after enqueuing experiments assets in the experiment and the alternative edit screens.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_enqueue_experiment_assets' );

		if ( $this->is_heatmap_request() ) {
			$this->add_heatmap_result_assets();
		} else {
			$this->add_experiment_result_assets();
		}//end if
	}//end enqueue_assets()

	private function add_experiment_result_assets() {

		$experiment = nab_get_experiment( absint( nab_array_get( $_GET, 'experiment', 0 ) ) ); // phpcs:ignore

		wp_enqueue_style( 'nab-results-page' );
		nab_enqueue_script_with_auto_deps( 'nab-results-page', 'results-page', true );

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "results", %s );
			} );
		} )();';

		$settings = array(
			'experimentId'     => $experiment->get_id(),
			'staging'          => nab_is_staging(),
			'isPublicView'     => nab_is_public_result_view(),
			'isReadOnlyActive' => nab_is_experiment_result_public( $experiment->get_id() ),
		);

		wp_add_inline_script(
			'nab-results-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}//end add_experiment_result_assets()

	private function add_heatmap_result_assets() {

		if ( isset( $_GET['heatmap'] ) && ! is_numeric( $_GET['heatmap'] ) ) { // phpcs:ignore
			wp_die( esc_html( _x( 'Invalid variant.', 'text', 'nelio-ab-testing' ) ) );
		}//end if

		$experiment = nab_get_experiment( absint( nab_array_get( $_GET, 'experiment', 0 ) ) ); // phpcs:ignore
		if ( isset( $_GET['heatmap'] ) ) { // phpcs:ignore
			$alt_idx = absint( $_GET['heatmap'] ); // phpcs:ignore
		} else {
			$alt_idx = 0;
		}//end if

		$alternative = nab_array_get( $experiment->get_alternatives(), $alt_idx, false );
		if ( 'nab/heatmap' !== $experiment->get_type() && empty( $alternative ) ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			wp_die(
				esc_html(
					sprintf(
						/* translators: 1 -> variant index, 2 -> experiment name */
						_x( 'Variant %1$s not found in test %2$s.', 'text', 'nelio-ab-testing' ),
						$alt_idx,
						$helper->get_non_empty_name( $experiment )
					)
				)
			);
		}//end if

		wp_enqueue_style( 'nab-heatmap-results-page' );
		nab_enqueue_script_with_auto_deps( 'nab-heatmap-results-page', 'heatmap-results-page', true );

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "nab-main", %s );
			} );
		} )();';

		$settings = array(
			'alternativeIndex' => $alt_idx,
			'endDate'          => $experiment->get_end_date(),
			'experimentId'     => $experiment->get_id(),
			'experimentType'   => $experiment->get_type(),
			'firstDayOfWeek'   => get_option( 'start_of_week', 0 ),
			'isStaging'        => nab_is_staging(),
			'isPublicView'     => nab_is_public_result_view(),
			'isReadOnlyActive' => nab_is_experiment_result_public( $experiment->get_id() ),
			'siteId'           => nab_get_site_id(),
		);

		wp_add_inline_script(
			'nab-heatmap-results-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}//end add_heatmap_result_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		$title = $this->page_title;
		// phpcs:ignore
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-results-page.php';
	}//end display()

	public function maybe_render_standalone_heatmap_page() {
		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		if ( $this->is_heatmap_request() ) {
			include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-heatmap-page.php';
			die();
		}//end if
	}//end maybe_render_standalone_heatmap_page()

	public function is_heatmap_request() {
		$experiment_id = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0; // phpcs:ignore
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return false;
		}//end if
		return 'nab/heatmap' === $experiment->get_type() || isset( $_GET['heatmap'] ); // phpcs:ignore
	}//end is_heatmap_request()

	private function does_request_have_an_experiment() {
		return isset( $_GET['experiment'] ) && absint( $_GET['experiment'] ); // phpcs:ignore
	}//end does_request_have_an_experiment()

	private function remove_this_page_from_the_menu() {
		remove_submenu_page( 'nelio-ab-testing', $this->menu_slug );
	}//end remove_this_page_from_the_menu()

	private function remove_experiments_list_from_menu() {
		remove_submenu_page( 'nelio-ab-testing', 'edit.php?post_type=nab_experiment' );
	}//end remove_experiments_list_from_menu()
}//end class
