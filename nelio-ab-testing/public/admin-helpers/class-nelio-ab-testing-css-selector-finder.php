<?php
/**
 * A file to discover CSS selectors.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/admin-helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required script for discovering CSS selectors.
 */
class Nelio_AB_Testing_Css_Selector_Finder {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Css_Selector_Finder|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Css_Selector_Finder
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'nab_public_init', array( $this, 'maybe_simulate_preview_params' ), 1 );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Callback to add preview arguments in global `$_GET` to simulate a preview.
	 *
	 * @return void
	 */
	public function maybe_simulate_preview_params() {

		if ( ! $this->should_css_selector_finder_be_loaded() ) {
			return;
		}

		$experiment_id = $this->get_experiment_id();
		$alt_idx       = $this->get_alternative_index();
		$timestamp     = time();
		$secret        = nab_get_api_secret();
		$nonce         = md5( "nab-preview-{$experiment_id}-{$alt_idx}-{$timestamp}-{$secret}" );

		$_GET['nab-preview'] = true;
		$_GET['timestamp']   = $timestamp;
		$_GET['nabnonce']    = $nonce;

		$args = array(
			'nab-css-selector-finder' => 'true',
			'experiment'              => $experiment_id,
			'alternative'             => $alt_idx,
		);
		add_filter( 'nab_is_preview_browsing_enabled', '__return_true' );
		add_filter( 'nab_preview_browsing_args', nab_return_constant( $args ) );
	}

	/**
	 * Callback to disable split testing in CSS selector finder.
	 *
	 * @param bool $disabled Whether split testing is disabled or not.
	 *
	 * @return bool
	 */
	public function should_split_testing_be_disabled( $disabled ) {

		if ( $this->should_css_selector_finder_be_loaded() ) {
			return true;
		}

		return $disabled;
	}

	/**
	 * Callback to set current visitor to none when needed.
	 *
	 * @param bool $anonymize Whether the current user is anonymous or not.
	 *
	 * @return bool
	 */
	public function should_simulate_anonymous_visitor( $anonymize ) {

		if ( $this->should_css_selector_finder_be_loaded() ) {
			return true;
		}

		return $anonymize;
	}

	/**
	 * Callback to enqueue assets to run the CSS selector finder.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		if ( ! $this->should_css_selector_finder_be_loaded() ) {
			return;
		}

		nab_enqueue_script_with_auto_deps(
			'nab-css-selector-finder',
			'css-selector-finder',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_enqueue_style(
			'nab-css-selector-finder',
			nelioab()->plugin_url . '/assets/dist/css/css-selector-finder.css',
			array(),
			nelioab()->plugin_version
		);
	}

	/**
	 * Whether the CSS selector finder is active in the current request or not.
	 *
	 * @return bool
	 */
	public function should_css_selector_finder_be_loaded() {

		if ( ! $this->get_experiment_id() ) {
			return false;
		}

		if ( false === $this->get_alternative_index() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['nab-css-selector-finder'] );
	}

	/**
	 * Gets current experiment from global `$_GET` variable.
	 *
	 * @return int|false
	 */
	private function get_experiment_id() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['experiment'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( $_GET['experiment'] );
	}

	/**
	 * Gets current alternative index from global `$_GET` variable.
	 *
	 * @return int|false
	 */
	private function get_alternative_index() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['alternative'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_numeric( $_GET['alternative'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( $_GET['alternative'] );
	}
}
