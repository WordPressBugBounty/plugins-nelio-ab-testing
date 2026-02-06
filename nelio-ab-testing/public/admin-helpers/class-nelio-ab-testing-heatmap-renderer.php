<?php
/**
 * A file to render heatmaps, scrollmaps and confetti.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/admin-helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required script for rendering heatmaps, scrollmaps and confetti.
 */
class Nelio_AB_Testing_Heatmap_Renderer {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Heatmap_Renderer|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Heatmap_Renderer
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

		add_filter( 'body_class', array( $this, 'maybe_add_heatmap_class' ) );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Callback to add an additional `nab-heatmap` class in the body when viewing heatmaps.
	 *
	 * @param list<string> $classes List of classes.
	 *
	 * @return list<string>
	 */
	public function maybe_add_heatmap_class( $classes ) {
		if ( ! nab_is_heatmap() ) {
			return $classes;
		}
		$classes = array_merge( $classes, array( 'nab-heatmap' ) );
		return array_values( array_unique( $classes ) );
	}

	/**
	 * Callback to disable split testing in heatmap.
	 *
	 * @param bool $disabled Whether split testing is disabled or not.
	 *
	 * @return bool
	 */
	public function should_split_testing_be_disabled( $disabled ) {

		if ( nab_is_heatmap() ) {
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

		if ( nab_is_heatmap() ) {
			return true;
		}

		return $anonymize;
	}

	/**
	 * Callback to enqueue heatmap assets when needed.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		if ( ! nab_is_heatmap() ) {
			return;
		}

		nab_enqueue_script_with_auto_deps(
			'nab-heatmap-renderer',
			'heatmap-renderer',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_enqueue_style( 'dashicons' );
	}
}
