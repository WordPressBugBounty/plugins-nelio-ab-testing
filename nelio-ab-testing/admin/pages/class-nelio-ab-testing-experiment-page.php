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
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		parent::init();

		add_action( 'admin_menu', array( $this, 'maybe_remove_this_page_from_the_menu' ), 999 );
		add_action( 'current_screen', array( $this, 'maybe_redirect_to_experiments_page' ) );
		add_action( 'current_screen', array( $this, 'die_if_params_are_invalid' ) );

		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
	}

	/**
	 * Callback to redirect the visitor to the experiments page if needed.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_experiments_page() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

		if ( ! $this->does_request_have_an_experiment() ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment' ) );
			exit;
		}
	}

	/**
	 * Callback to remove this page from the menu if not needed.
	 *
	 * @return void
	 */
	public function maybe_remove_this_page_from_the_menu() {

		if ( ! $this->is_current_screen_this_page() ) {
			$this->remove_this_page_from_the_menu();
		} else {
			$this->remove_experiments_list_from_menu();
		}
	}

	/**
	 * Callback to die if the params are invalid and the visitor can’t see this page.
	 *
	 * @return void
	 */
	public function die_if_params_are_invalid() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment_id = absint( $_GET['experiment'] ?? 0 );
		if ( 'nab_experiment' !== get_post_type( $experiment_id ) ) {
			wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
		}

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			wp_die( esc_html_x( 'Experiment not found', 'text', 'nelio-ab-testing' ) );
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			wp_die( esc_html( $can_be_edited->get_error_message() ) );
		}
	}

	// @Implements
	public function enqueue_assets() {

		/**
		 * Fires after enqueuing experiments assets in the experiment and the alternative edit screens.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_enqueue_experiment_assets' );

		wp_enqueue_media();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment = nab_get_experiment( absint( $_GET['experiment'] ?? 0 ) );
		if ( is_wp_error( $experiment ) ) {
			wp_die( esc_html_x( 'Experiment not found', 'text', 'nelio-ab-testing' ) );
		}

		if ( 'nab/heatmap' === $experiment->get_type() ) {
			$this->add_heatmap_editor_assets( $experiment->get_id() );
		} else {
			$this->add_experiment_editor_assets( $experiment->get_id() );
		}
	}

	// @Implements
	public function display() {
		$title = $this->page_title;
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-experiment-page.php';
	}

	/**
	 * Callback to add editor class to body tag if current screen is the editor page.
	 *
	 * @param string $classes Classes applied to the body tag.
	 *
	 * @return string
	 */
	public function add_body_classes( $classes ) {

		if ( ! $this->is_current_screen_this_page() ) {
			return $classes;
		}

		return $classes . ' nab-experiment-editor-page';
	}

	/**
	 * Adds regular experiment editor assets.
	 *
	 * @param int $experiment_id ID of the experiment.
	 *
	 * @return void
	 */
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
	}

	/**
	 * Adds heatmap editor assets.
	 *
	 * @param int $experiment_id ID of the experiment.
	 *
	 * @return void
	 */
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
	}

	/**
	 * Whether the request has an experiment ID in its GET args.
	 *
	 * @return bool
	 */
	private function does_request_have_an_experiment() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['experiment'] ) && absint( $_GET['experiment'] );
	}

	/**
	 * Removes this page from the menu.
	 *
	 * @return void
	 */
	private function remove_this_page_from_the_menu() {
		remove_submenu_page( 'nelio-ab-testing', $this->menu_slug );
	}

	/**
	 * Removes the experiments list page from the menu.
	 *
	 * @return void
	 */
	private function remove_experiments_list_from_menu() {
		remove_submenu_page( 'nelio-ab-testing', 'edit.php?post_type=nab_experiment' );
	}
}
