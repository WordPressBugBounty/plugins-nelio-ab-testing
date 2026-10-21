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
		add_action( 'current_screen', array( $this, 'handle_review_notice' ) );
		add_action( 'current_screen', array( $this, 'maybe_render_standalone_heatmap_page' ), 99 );
	}

	/**
	 * Callback to redirect the visitor to the experiments page if needed.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_experiments_page() {

		if ( ! $this->is_current_screen_this_page() ) {
			return; // @codeCoverageIgnore
		}

		if ( ! $this->does_request_have_an_experiment() ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment' ) );
			exit; // @codeCoverageIgnore
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
			return; // @codeCoverageIgnore
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment_id = absint( $_GET['experiment'] ?? 0 );
		$post_type     = get_post_type( $experiment_id );
		if ( ! empty( $post_type ) && 'nab_experiment' !== $post_type ) {
			wp_die(
				sprintf(
					/* translators: %1$s: post type */
					esc_html_x( 'Invalid post type: %1$s.', 'text', 'nelio-ab-testing' ),
					esc_html( $post_type )
				)
			);
		}

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
		}

		$status = $experiment->get_status();
		if ( ! in_array( $status, array( 'running', 'finished' ), true ) ) {
			wp_die( esc_html_x( 'You’re not allowed to view this page.', 'user', 'nelio-ab-testing' ) );
		}
	}

	// @Implements
	public function display() {
		$title = $this->page_title;
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-results-page.php';
	}

	/**
	 * Permanently dismisses the review request for the current user.
	 *
	 * @return void
	 */
	public function handle_review_notice() {
		if ( ! $this->is_current_screen_this_page() || empty( $_GET['nab_review_notice'] ) ) {
			return;
		}

		if ( 'dismiss' !== sanitize_text_field( wp_unslash( $_GET['nab_review_notice'] ) ) ) {
			return;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'read_nab_results' ) || nab_is_public_result_view() ) {
			return;
		}

		check_admin_referer( 'nab_review_notice' );
		update_user_meta( get_current_user_id(), '_nab_review_notice_dismissed', true );

		wp_safe_redirect( remove_query_arg( array( 'nab_review_notice', '_wpnonce' ) ) );
		exit; // @codeCoverageIgnore
	}

	/**
	 * Loads the heatmap page partial if the request is a heatmap request.
	 *
	 * @return void
	 */
	public function maybe_render_standalone_heatmap_page() {
		if ( ! $this->is_current_screen_this_page() ) {
			return; // @codeCoverageIgnore
		}

		if ( $this->is_heatmap_request() ) {
			include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-heatmap-page.php';
			nab_die();
		}
	}

	/**
	 * Whether the user has requested to see the results of a heatmap test or not.
	 *
	 * @return bool
	 */
	public function is_heatmap_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment_id = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0;
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 'nab/heatmap' === $experiment->get_type() || isset( $_GET['heatmap'] );
	}

	// @Implements
	protected function enqueue_assets() {

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

		if ( $this->is_heatmap_request() ) {
			$this->add_heatmap_result_assets();
		} else {
			$this->add_experiment_result_assets();
		}

		/**
		 * Fires after enqueuing experiments assets in the experiment and the alternative edit screens.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_enqueue_experiment_assets' );
	}

	/**
	 * Adds assets to view results of a regular experiment.
	 *
	 * @return void
	 */
	private function add_experiment_result_assets() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment = nab_get_experiment( absint( $_GET['experiment'] ?? 0 ) );
		if ( is_wp_error( $experiment ) ) {
			wp_die( esc_html( _x( 'Test not found.', 'text', 'nelio-ab-testing' ) ) );
		}

		nab_enqueue_script_with_auto_deps( 'nab-results-page', 'results-page', true );

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "nab-results", %s );
			} );
		} )();';

		$settings = array(
			'experimentId'     => $experiment->get_id(),
			'reviewNoticeUrl'  => $this->get_review_notice_url( $experiment ),
			'staging'          => nab_is_staging(),
			'isPublicView'     => nab_is_public_result_view(),
			'isReadOnlyActive' => $experiment->has_public_results(),
		);

		wp_add_inline_script(
			'nab-results-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}

	/**
	 * Gets the dismissal URL only when viewing a completed A/B test as a user.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 * @return string|false
	 */
	private function get_review_notice_url( $experiment ) {
		if (
			! is_admin() ||
			! is_user_logged_in() ||
			! current_user_can( 'read_nab_results' ) ||
			nab_is_public_result_view() ||
			'finished' !== $experiment->get_status() ||
			get_user_meta( get_current_user_id(), '_nab_review_notice_dismissed', true )
		) {
			return false;
		}

		return add_query_arg(
			array(
				'page'              => $this->menu_slug,
				'experiment'        => $experiment->get_id(),
				'nab_review_notice' => 'dismiss',
				'_wpnonce'          => wp_create_nonce( 'nab_review_notice' ),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Adds assets to render a heatmap.
	 *
	 * @return void
	 */
	private function add_heatmap_result_assets() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['heatmap'] ) && ! is_numeric( $_GET['heatmap'] ) ) {
			wp_die( esc_html( _x( 'Invalid variant.', 'text', 'nelio-ab-testing' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment = nab_get_experiment( absint( $_GET['experiment'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['heatmap'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$alt_idx = absint( $_GET['heatmap'] );
		} else {
			$alt_idx = 0;
		}

		$alternatives = $experiment->get_alternatives();
		$alternative  = $alternatives[ $alt_idx ] ?? false;
		if ( 'nab/heatmap' !== $experiment->get_type() && empty( $alternative ) ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: %1$s: Variant ID. %2$s: Experiment name. */
						_x( 'Variant %1$s not found in test %2$s.', 'text', 'nelio-ab-testing' ),
						$alt_idx,
						$experiment->get_non_empty_name()
					)
				)
			);
		}

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
			'isReadOnlyActive' => $experiment->has_public_results(),
			'siteId'           => nab_get_site_id(),
		);

		wp_add_inline_script(
			'nab-heatmap-results-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
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
