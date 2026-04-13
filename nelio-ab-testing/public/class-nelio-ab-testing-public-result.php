<?php
/**
 * Some helper functions to render results publicly
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @since      7.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Some helper functions used during frontend runtime.
 */
class Nelio_AB_Testing_Public_Result {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'pre_get_posts', array( $this, 'maybe_no_index' ) );
		add_action( 'set_current_user', array( $this, 'maybe_simulate_anonymous_visitor' ), 99 );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'template_include', array( $this, 'maybe_use_result_template' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Callback to set the noindex robots tags in public result view.
	 *
	 * @return void
	 */
	public static function maybe_no_index() {

		if ( ! nab_is_public_result_view() ) {
			return;
		}

		if ( ! headers_sent() ) {
			nocache_headers();                 // @codeCoverageIgnore
			header( 'X-Robots-Tag: noindex' ); // @codeCoverageIgnore
		}

		if ( function_exists( 'wp_robots_no_robots' ) ) {
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		}
	}

	/**
	 * Callback to set current user to none in public result view.
	 *
	 * @return void
	 */
	public function maybe_simulate_anonymous_visitor() {

		if ( ! nab_is_public_result_view() ) {
			return;
		}

		wp_set_current_user( 0 );
	}

	/**
	 * Callback to disable split testing.
	 *
	 * @param bool $disabled Whether split testing is disabled or not.
	 *
	 * @return bool
	 */
	public function should_split_testing_be_disabled( $disabled ) {

		if ( ! nab_is_public_result_view() ) {
			return $disabled;
		}

		return true;
	}

	/**
	 * Callback to use public result template in public result view.
	 *
	 * @param string $template Current template.
	 *
	 * @return string
	 */
	public function maybe_use_result_template( $template ) {

		if ( ! nab_is_public_result_view() ) {
			return $template;
		}

		// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
		add_filter( 'show_admin_bar', '__return_false' );

		return nelioab()->plugin_path . '/includes/templates/public-result.php';
	}

	/**
	 * Callback to enqueue assets in public result view.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets() {

		if ( ! nab_is_public_result_view() ) {
			return;
		}

		$admin = new Nelio_AB_Testing_Admin();
		$admin->register_assets();

		$page = new Nelio_AB_Testing_Results_Page();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$old_get_page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		$_GET['page'] = 'nelio-ab-testing-experiment-view';
		$page->maybe_enqueue_assets();
		$_GET['page'] = $old_get_page;
		if ( empty( $old_get_page ) ) {
			unset( $_GET['page'] );
		}

		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );

		wp_add_inline_style(
			'nab-results-page',
			'.nab-results-experiment-layout .nab-results-experiment-header { left: 0; top: 0; }'
		);
	}
}
