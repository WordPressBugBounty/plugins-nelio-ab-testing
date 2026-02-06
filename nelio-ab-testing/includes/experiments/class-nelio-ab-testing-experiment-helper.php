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

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Experiment_Helper|null
	 */
	protected static $instance;

	/**
	 * Running experiments.
	 *
	 * @var list<Nelio_AB_Testing_Experiment>|null
	 */
	private $running_experiments;

	/**
	 * Running heatmaps.
	 *
	 * @var list<Nelio_AB_Testing_Heatmap>|null
	 */
	private $running_heatmaps;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Experiment_Helper
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance                      = new self();
			self::$instance->running_experiments = null;
			self::$instance->running_heatmaps    = null;
		}
		return self::$instance;
	}

	/**
	 * Returns the name of the experiment (if it has one) next to its ID.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return non-empty-string
	 */
	public function get_non_empty_name( $experiment ) {

		$name = trim( $experiment->get_name() );
		$id   = $experiment->get_id();

		if ( empty( $name ) ) {
			return "{$id}";
		}

		$pattern = '“%s” (%d)';
		return sprintf( $pattern, $name, $id );
	}

	/**
	 * Returns a list of IDs with the corresponding running split testing experiments.
	 *
	 * @return list<Nelio_AB_Testing_Experiment> a list of IDs with the corresponding running split testing experiments.
	 *
	 * @since 5.0.0
	 */
	public function get_running_experiments() {
		if ( ! is_null( $this->running_experiments ) ) {
			return $this->running_experiments;
		}

		$exps = array_map( fn ( $eid ) => nab_get_experiment( $eid ), nab_get_running_experiment_ids() );
		$exps = array_filter( $exps, fn( $e ) => ! is_wp_error( $e ) );
		$exps = array_values( $exps );

		$this->running_experiments = $exps;
		return $exps;
	}

	/**
	 * Returns the list of running nab/heatmap experiments.
	 *
	 * @return list<Nelio_AB_Testing_Heatmap>
	 *
	 * @since 5.0.0
	 */
	public function get_running_heatmaps() {
		if ( ! is_null( $this->running_heatmaps ) ) {
			return $this->running_heatmaps;
		}

		$exps = array_map( fn ( $eid ) => nab_get_experiment( $eid ), nab_get_running_heatmap_ids() );
		$exps = array_filter( $exps, fn( $e ) => $e instanceof Nelio_AB_Testing_Heatmap );
		$exps = array_values( $exps );

		$this->running_heatmaps = $exps;
		return $exps;
	}

	/**
	 * Checks all running experiments and adds alternative post IDs to the given IDs.
	 *
	 * @param list<int> $ids List of post IDs.
	 *
	 * @return list<int> List of post IDs (including variants).
	 *
	 * @since 6.0.4
	 */
	public function add_alternative_post_ids( $ids ) {
		$alt_ids = $this->get_alternative_post_ids();
		$result  = array();

		foreach ( $ids as $id ) {
			$result = array_merge(
				$result,
				$alt_ids[ $id ] ?? array( $id )
			);
		}

		return $result;
	}

	/**
	 * Returns the list of alternative post IDs.
	 *
	 * @return array<int,list<int>>
	 */
	private function get_alternative_post_ids() {
		$result = array();

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		foreach ( $experiments as $experiment ) {
			$post_ids = $experiment->get_tested_posts();
			if ( ! empty( $post_ids ) ) {
				$result[ $post_ids[0] ] = $post_ids;
			}
		}

		return $result;
	}
}
