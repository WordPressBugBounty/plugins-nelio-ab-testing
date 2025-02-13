<?php
/**
 * Some helper functions to work with experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 */
class Nelio_AB_Testing_Experiment_Helper {

	protected static $instance;

	private $running_experiments;
	private $running_heatmaps;

	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

			self::$instance->running_experiments = false;
			self::$instance->running_heatmaps    = false;

		}//end if

		return self::$instance;
	}//end instance()

	public function get_non_empty_name( $experiment ) {

		$name = trim( $experiment->get_name() );
		$id   = $experiment->get_id();

		if ( empty( $name ) ) {
			return "{$id}";
		}//end if

		$pattern = '“%s” (%d)';
		return sprintf( $pattern, $name, $id );
	}//end get_non_empty_name()

	public function get_running_experiments() {

		if ( false !== $this->running_experiments ) {
			return $this->running_experiments;
		}//end if

		$this->running_experiments = array_map(
			function ( $experiment_id ) {
				return nab_get_experiment( $experiment_id );
			},
			nab_get_running_experiment_ids()
		);

		return $this->running_experiments;
	}//end get_running_experiments()

	public function get_running_heatmaps() {

		if ( false !== $this->running_heatmaps ) {
			return $this->running_heatmaps;
		}//end if

		$this->running_heatmaps = array_map(
			function ( $experiment_id ) {
				return nab_get_experiment( $experiment_id );
			},
			nab_get_running_heatmap_ids()
		);

		return $this->running_heatmaps;
	}//end get_running_heatmaps()

	/**
	 * Checks all running experiments and adds alternative post IDs to the given IDs.
	 *
	 * @param array $ids list of post IDs.
	 *
	 * @return array list of post IDs (including variants).
	 *
	 * @since 6.0.4
	 */
	public function add_alternative_post_ids( $ids ) {
		$alt_ids = $this->get_alternative_post_ids();
		$result  = array();

		foreach ( $ids as $id ) {
			$result = array_merge(
				$result,
				nab_array_get( $alt_ids, $id, array( $id ) )
			);
		}//end foreach

		return $result;
	}//end add_alternative_post_ids()

	private function get_alternative_post_ids() {
		$result = array();

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		foreach ( $experiments as $experiment ) {
			$post_ids = $experiment->get_tested_posts();
			if ( ! empty( $post_ids ) ) {
				$result[ $post_ids[0] ] = $post_ids;
			}//end if
		}//end foreach

		return $result;
	}//end get_alternative_post_ids()
}//end class
