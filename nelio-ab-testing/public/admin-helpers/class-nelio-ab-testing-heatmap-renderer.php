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

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {

		add_filter( 'body_class', array( $this, 'maybe_add_heatmap_class' ) );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}//end init()

	public function maybe_add_heatmap_class( $classes ) {
		if ( ! nab_is_heatmap() ) {
			return $classes;
		}//end if
		$classes = array_merge( $classes, array( 'nab-heatmap' ) );
		return array_values( array_unique( $classes ) );
	}//end maybe_add_heatmap_class()

	public function should_split_testing_be_disabled( $disabled ) {

		if ( nab_is_heatmap() ) {
			return true;
		}//end if

		return $disabled;
	}//end should_split_testing_be_disabled()

	public function should_simulate_anonymous_visitor( $anonymize ) {

		if ( nab_is_heatmap() ) {
			return true;
		}//end if

		return $anonymize;
	}//end should_simulate_anonymous_visitor()

	public function enqueue_assets() {

		if ( ! nab_is_heatmap() ) {
			return;
		}//end if

		nab_enqueue_script_with_auto_deps(
			'nab-heatmap-renderer',
			'heatmap-renderer',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_enqueue_style( 'dashicons' );
	}//end enqueue_assets()
}//end class
