<?php
/**
 * This file defines the user interface for editing an experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Edit page.
 */
class Nelio_AB_Testing_Experiment_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Edit Test', 'text', 'nelio-ab-testing' ),
			_x( 'Tests', 'text', 'nelio-ab-testing' ),
			'edit_nab_experiments',
			'nelio-ab-testing-experiment-edit'
		);
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function init() {

		parent::init();

		add_action( 'admin_menu', array( $this, 'maybe_remove_this_page_from_the_menu' ), 999 );
		add_action( 'current_screen', array( $this, 'maybe_redirect_to_experiments_page' ) );
		add_action( 'current_screen', array( $this, 'die_if_params_are_invalid' ) );

		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
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
		if ( in_array( $status, array( 'running', 'finished' ), true ) ) {
			wp_die( esc_html_x( 'You’re not allowed to view this page.', 'user', 'nelio-ab-testing' ) );
			return;
		}//end if
	}//end die_if_params_are_invalid()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		/**
		 * Fires after enqueuing experiments assets in the experiment and the alternative edit screens.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_enqueue_experiment_assets' );

		wp_enqueue_media();

		$experiment = nab_get_experiment( absint( nab_array_get( $_GET, 'experiment', 0 ) ) ); // phpcs:ignore
		if ( 'nab/heatmap' === $experiment->get_type() ) {
			$this->add_heatmap_editor_assets( $experiment->get_id() );
		} else {
			$this->add_experiment_editor_assets( $experiment->get_id() );
		}//end if
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		$title = $this->page_title;
		// phpcs:ignore
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-experiment-page.php';
	}//end display()

	public function add_body_classes( $classes ) {

		if ( ! $this->is_current_screen_this_page() ) {
			return $classes;
		}//end if

		return $classes . ' nab-experiment-editor-page';
	}//end add_body_classes()

	private function add_experiment_editor_assets( $experiment_id ) {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.editor.initializeExperimentEditor( "nab-editor", %d );
			} );
		} )();';

		wp_enqueue_script( 'nab-editor' );
		wp_add_inline_script(
			'nab-editor',
			sprintf(
				$script,
				wp_json_encode( $experiment_id )
			)
		);

		wp_enqueue_style( 'nab-editor' );
	}//end add_experiment_editor_assets()

	private function add_heatmap_editor_assets( $experiment_id ) {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.heatmapEditor.initializeExperimentEditor( "nab-editor", %d );
			} );
		} )();';

		wp_enqueue_script( 'nab-heatmap-editor' );
		wp_add_inline_script(
			'nab-heatmap-editor',
			sprintf(
				$script,
				wp_json_encode( $experiment_id )
			)
		);

		wp_enqueue_style( 'nab-heatmap-editor' );
	}//end add_heatmap_editor_assets()

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
