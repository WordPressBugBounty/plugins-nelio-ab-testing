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
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'nab_is_preview_browsing_enabled', array( $this, 'maybe_enable_browsing_during_preview' ) );
		add_filter( 'nab_preview_browsing_args', array( $this, 'maybe_add_css_selector_finder_param' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Callback to enable browsing during preview when CSS selector finder is required.
	 *
	 * @param bool $enabled Browsing enabled.
	 *
	 * @return bool
	 */
	public function maybe_enable_browsing_during_preview( $enabled ) {
		if ( ! $this->should_css_selector_finder_be_loaded() ) {
			return $enabled;
		}
		return true;
	}

	/**
	 * Callback to add the CSS selector finder argument in the list of arguments used during preview when CSS selector finder is active.
	 *
	 * @param array<string,mixed> $args The arguments that should be added in URL to allow preview browsing.
	 *
	 * @return array<string,mixed>
	 */
	public function maybe_add_css_selector_finder_param( $args ) {
		if ( ! $this->should_css_selector_finder_be_loaded() ) {
			return $args;
		}
		$args['nab-css-selector-finder'] = true;
		return $args;
	}

	/**
	 * Callback to enqueue assets to run the CSS selector finder.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets() {
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return nab_is_preview() && isset( $_GET['nab-css-selector-finder'] );
	}
}
