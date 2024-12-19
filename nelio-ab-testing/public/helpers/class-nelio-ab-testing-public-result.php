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

	protected static $instance;

	public static function instance(): Nelio_AB_Testing_Public_Result {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {

		add_action( 'pre_get_posts', array( $this, 'maybe_no_index' ) );
		add_action( 'set_current_user', array( $this, 'maybe_simulate_anonymous_visitor' ), 99 );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'template_include', array( $this, 'maybe_use_result_template' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}//end init()

	public static function maybe_no_index() {

		if ( ! nab_is_public_result_view() ) {
			return;
		}//end if

		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'X-Robots-Tag: noindex' );
		}//end if

		if ( function_exists( 'wp_robots_no_robots' ) ) {
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		}//end if
	}//end maybe_no_index()

	public function maybe_simulate_anonymous_visitor() {

		if ( ! nab_is_public_result_view() ) {
			return;
		}//end if

		wp_set_current_user( 0 );
	}//end maybe_simulate_anonymous_visitor()

	public function should_split_testing_be_disabled( $disabled ) {

		if ( ! nab_is_public_result_view() ) {
			return $disabled;
		}//end if

		return true;
	}//end should_split_testing_be_disabled()

	public function maybe_use_result_template( $template ) {

		if ( ! nab_is_public_result_view() ) {
			return $template;
		}//end if

		add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore

		return nelioab()->plugin_path . '/includes/templates/public-result.php';
	}//end maybe_use_result_template()

	public function maybe_enqueue_assets() {

		if ( ! nab_is_public_result_view() ) {
			return;
		}//end if

		$aux = Nelio_AB_Testing_Admin::instance();
		$aux->register_assets();

		$page = new Nelio_AB_Testing_Results_Page();
		$page->enqueue_assets();

		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );

		wp_add_inline_style(
			'nab-results-page',
			'.nab-results-experiment-layout .nab-results-experiment-header { left: 0; top: 0; }'
		);
	}//end maybe_enqueue_assets()
}//end class
