<?php
/**
 * This file keeps track of all loaded-in-memory experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      8.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Experiment manager.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      8.3.0
 */
class Nelio_AB_Testing_Experiment_Manager {

	private const CACHE_GROUP = 'nelio_ab_testing';

	private const ALL_EXPERIMENT_IDS = 'all_experiment_ids';

	private const RUNNING_EXPERIMENT_IDS = 'running_experiment_ids';
	private const RUNNING_HEATMAP_IDS    = 'running_heatmap_ids';

	private const HAS_PAUSED_EXPERIMENTS = 'has_paused_experiments';

	/**
	 * List of loaded experiments.
	 *
	 * @var array<Nelio_AB_Testing_Experiment|WP_Error>
	 */
	private $experiments;

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'nab_after_create_experiment', array( $this, 'add_new_experiment_to_cache' ) );

		add_action( 'nab_save_experiment', array( $this, 'maybe_clear_running_cache' ) );
		add_action( 'nab_start_experiment', array( $this, 'maybe_clear_running_cache' ) );
		add_action( 'nab_pause_experiment', array( $this, 'maybe_clear_running_cache' ) );
		add_action( 'nab_resume_experiment', array( $this, 'maybe_clear_running_cache' ) );
		add_action( 'nab_stop_experiment', array( $this, 'maybe_clear_running_cache' ) );

		add_action( 'nab_save_experiment', array( $this, 'clear_has_paused_cache' ) );
		add_action( 'nab_pause_experiment', array( $this, 'clear_has_paused_cache' ) );
		add_action( 'nab_resume_experiment', array( $this, 'clear_has_paused_cache' ) );

		add_action( 'nab_stop_experiment', array( $this, 'save_results_on_stop' ) );

		add_action( 'nab_after_delete_experiment', array( $this, 'clear' ) );
		add_action( 'nab_after_delete_experiment', array( $this, 'delete_experiment_from_cache' ) );
	}

	/**
	 * Returns the list of ids of running split testing experiments.
	 *
	 * @return list<int> the list of ids of running split testing experiments.
	 */
	public function get_all_experiment_ids() {
		/** @var list<int>|false */
		$ids = wp_cache_get( self::ALL_EXPERIMENT_IDS, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $ids ) {
			/** @var wpdb */
			global $wpdb;
			/** @var list<int> */
			$ids = array_map(
				'absint',
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->get_col(
					$wpdb->prepare(
						'SELECT ID FROM %i p
							WHERE p.post_type = %s',
						$wpdb->posts,
						'nab_experiment'
					)
				)
			);
			wp_cache_set( self::ALL_EXPERIMENT_IDS, $ids, self::CACHE_GROUP );
		}
		return $ids;
	}

	/**
	 * Gets the experiment from the database if found. Returns error otherwise.
	 *
	 * @param int|WP_Post|Nelio_AB_Testing_Experiment $experiment The experiment.
	 *
	 * @return Nelio_AB_Testing_Experiment|WP_Error
	 */
	public function get_experiment( $experiment ) {

		if ( is_numeric( $experiment ) ) {
			$experiment_id = $experiment;
		} elseif ( isset( $experiment->ID ) ) {
			$experiment_id = absint( $experiment->ID );
		}

		if ( empty( $experiment_id ) ) {
			return new WP_Error( 'experiment-id-not-found', _x( 'Test not found.', 'text', 'nelio-ab-testing' ) );
		}

		if ( ! empty( $this->experiments[ $experiment_id ] ) ) {
			return $this->experiments[ $experiment_id ];
		}

		$experiment = get_post( $experiment_id );
		if ( empty( $experiment ) ) {
			$this->experiments[ $experiment_id ] = new WP_Error( 'experiment-id-not-found', _x( 'Test not found.', 'text', 'nelio-ab-testing' ) );
			return $this->experiments[ $experiment_id ];
		}

		if ( 'nab_experiment' !== $experiment->post_type ) {
			$this->experiments[ $experiment_id ] = new WP_Error( 'invalid-experiment', _x( 'Invalid test.', 'text', 'nelio-ab-testing' ) );
			return $this->experiments[ $experiment_id ];
		}

		$experiment_type = get_post_meta( $experiment->ID, '_nab_experiment_type', true );
		if ( empty( $experiment_type ) ) {
			$this->experiments[ $experiment_id ] = new WP_Error( 'invalid-experiment', _x( 'Invalid test.', 'text', 'nelio-ab-testing' ) );
			return $this->experiments[ $experiment_id ];
		}

		if ( 'nab/heatmap' === $experiment_type ) {
			$this->experiments[ $experiment_id ] = new Nelio_AB_Testing_Heatmap( $experiment );
			return $this->experiments[ $experiment_id ];
		}

		$this->experiments[ $experiment_id ] = new Nelio_AB_Testing_Experiment( $experiment );
		return $this->experiments[ $experiment_id ];
	}

	/**
	 * Creates a new experiment of the given type and returns it.
	 *
	 * @param string $experiment_type the experiment type.
	 *
	 * @return Nelio_AB_Testing_Experiment|WP_Error
	 */
	public function create_experiment( $experiment_type ) {

		$edit_capability =
			'nab/php' === $experiment_type
				? 'edit_nab_php_experiments'
				: 'edit_nab_experiments';
		if ( ! current_user_can( $edit_capability ) ) {
			return new WP_Error(
				'missing-capability',
				_x( 'Sorry, you are not allowed to create this type of test.', 'text', 'nelio-ab-testing' )
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'nab_experiment',
				'post_status' => 'draft',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id; // @codeCoverageIgnore
		}

		update_post_meta( $post_id, '_nab_experiment_type', $experiment_type );
		update_post_meta( $post_id, '_nab_version', nelioab()->plugin_version );

		$experiment = $this->get_experiment( $post_id );
		assert( ! is_wp_error( $experiment ) );

		/**
		 * Runs after an experiment has been created.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
		 *
		 * @since 8.3.0
		 */
		do_action( 'nab_after_create_experiment', $experiment );

		return $experiment;
	}

	/**
	 * Returns a list of IDs with the corresponding running split testing experiments.
	 *
	 * @return list<Nelio_AB_Testing_Experiment> a list of IDs with the corresponding running split testing experiments.
	 */
	public function get_running_experiments() {
		$experiments = array_map( fn ( $eid ) => $this->get_experiment( $eid ), $this->get_running_experiment_ids() );
		$experiments = array_filter( $experiments, fn( $e ) => ! is_wp_error( $e ) );
		$experiments = array_values( $experiments );
		return $experiments;
	}

	/**
	 * Returns the list of running nab/heatmap experiments.
	 *
	 * @return list<Nelio_AB_Testing_Heatmap>
	 */
	public function get_running_heatmaps() {
		$heatmaps = array_map( fn ( $hid ) => $this->get_experiment( $hid ), $this->get_running_heatmap_ids() );
		$heatmaps = array_filter( $heatmaps, fn( $h ) => $h instanceof Nelio_AB_Testing_Heatmap );
		$heatmaps = array_values( $heatmaps );
		return $heatmaps;
	}

	/**
	 * Whether there are experiments running or not.
	 *
	 * @return bool
	 */
	public function has_running_experiments() {
		$experiment_ids = $this->get_running_experiment_ids();
		return ! empty( $experiment_ids );
	}

	/**
	 * Whether there are paused experiments or not.
	 *
	 * @return bool
	 */
	public function has_paused_experiments() {
		/** @var array{value?:boolean}|false */
		$result = wp_cache_get( self::HAS_PAUSED_EXPERIMENTS, self::CACHE_GROUP, false, $found );
		if ( ! $found || ! isset( $result['value'] ) ) {
			/** @var wpdb */
			global $wpdb;
			$value = ! empty(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->get_var(
					$wpdb->prepare(
						'SELECT ID FROM %i p, %i m
						WHERE
							p.post_type = %s AND p.post_status IN (%s,%s) AND
							p.ID = m.post_id AND
							m.meta_key = %s AND m.meta_value != %s
						LIMIT 1
					',
						$wpdb->posts,
						$wpdb->postmeta,
						'nab_experiment',
						'nab_paused',
						'nab_paused_draft',
						'_nab_experiment_type',
						'nab/heatmap'
					)
				)
			);
			$result = array( 'value' => $value );
			wp_cache_set( self::HAS_PAUSED_EXPERIMENTS, $result, self::CACHE_GROUP );
		}
		return $result['value'];
	}

	/**
	 * Callback to clear all internal caches.
	 *
	 * @return void
	 */
	public function clear() {
		wp_cache_delete( self::ALL_EXPERIMENT_IDS, self::CACHE_GROUP );
		wp_cache_delete( self::RUNNING_EXPERIMENT_IDS, self::CACHE_GROUP );
		wp_cache_delete( self::RUNNING_HEATMAP_IDS, self::CACHE_GROUP );
	}

	/**
	 * Callback to clear has paused expeirments cache.
	 *
	 * @return void
	 */
	public function clear_has_paused_cache() {
		wp_cache_delete( self::HAS_PAUSED_EXPERIMENTS, self::CACHE_GROUP );
	}

	/**
	 * Callback to clear running cache when experiment moves from being not being running to running (or viceversa).
	 *
	 * @param Nelio_AB_Testing_Experiment|int $experiment Experiment.
	 *
	 * @return void
	 */
	public function maybe_clear_running_cache( $experiment ) {
		if ( is_int( $experiment ) && 'nab_experiment' !== get_post_type( $experiment ) ) {
			return; // @codeCoverageIgnore
		}

		$experiment_id = is_int( $experiment ) ? $experiment : absint( $experiment->ID );
		$experiment    = $experiment instanceof Nelio_AB_Testing_Experiment ? $experiment : nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return; // @codeCoverageIgnore
		}

		$is_running = 'running' === $experiment->get_status();

		$was_experiment_running = in_array( $experiment_id, $this->get_running_experiment_ids(), true );
		if ( $is_running !== $was_experiment_running ) {
			wp_cache_delete( self::RUNNING_EXPERIMENT_IDS, self::CACHE_GROUP );
		}

		$was_heatmap_running = in_array( $experiment_id, $this->get_running_heatmap_ids(), true );
		if ( $is_running !== $was_heatmap_running ) {
			wp_cache_delete( self::RUNNING_HEATMAP_IDS, self::CACHE_GROUP );
		}
	}

	/**
	 * Retrieves the latest resutls available of the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
	 *
	 * @return void
	 */
	public function save_results_on_stop( $experiment ) {
		// Simulate a request to view the results, which effectively saves them in the database as a post meta.
		nab_get_experiment_results( $experiment ); // @codeCoverageIgnore
	}

	/**
	 * Deletes experiment from cache.
	 *
	 * @param int $experiment_id Experiment ID.
	 *
	 * @return void
	 */
	public function delete_experiment_from_cache( $experiment_id ) {
		unset( $this->experiments[ $experiment_id ] );
	}

	/**
	 * Adds a new experiment to the local cache.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return void
	 */
	public function add_new_experiment_to_cache( $experiment ) {
		$this->experiments[ $experiment->get_id() ] = $experiment;

		$ids   = $this->get_all_experiment_ids();
		$ids[] = $experiment->get_id();
		wp_cache_set( self::ALL_EXPERIMENT_IDS, $ids, self::CACHE_GROUP );
	}

	/**
	 * Returns the list of ids of running split testing experiments.
	 *
	 * @return list<int> the list of ids of running split testing experiments.
	 */
	private function get_running_experiment_ids() {
		/** @var list<int>|false */
		$ids = wp_cache_get( self::RUNNING_EXPERIMENT_IDS, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $ids ) {
			/** @var wpdb */
			global $wpdb;
			/** @var list<int> */
			$ids = array_map(
				'absint',
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->get_col(
					$wpdb->prepare(
						'SELECT ID FROM %i p, %i m
							WHERE
								p.post_type = %s AND p.post_status = %s AND
								p.ID = m.post_id AND
								m.meta_key = %s AND m.meta_value != %s',
						$wpdb->posts,
						$wpdb->postmeta,
						'nab_experiment',
						'nab_running',
						'_nab_experiment_type',
						'nab/heatmap'
					)
				)
			);
			wp_cache_set( self::RUNNING_EXPERIMENT_IDS, $ids, self::CACHE_GROUP );
		}
		return $ids;
	}

	/**
	 * Returns a list of IDs corresponding to running heatmaps.
	 *
	 * @return list<int> a list of IDs corresponding to running heatmaps.
	 */
	private function get_running_heatmap_ids() {
		/** @var list<int>|false */
		$ids = wp_cache_get( self::RUNNING_HEATMAP_IDS, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $ids ) {
			/** @var wpdb */
			global $wpdb;
			/** @var list<int> */
			$ids = array_map(
				'absint',
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->get_col(
					$wpdb->prepare(
						'SELECT ID FROM %i p, %i m
							WHERE
								p.post_type = %s AND p.post_status = %s AND
								p.ID = m.post_id AND
								m.meta_key = %s AND m.meta_value = %s',
						$wpdb->posts,
						$wpdb->postmeta,
						'nab_experiment',
						'nab_running',
						'_nab_experiment_type',
						'nab/heatmap'
					)
				)
			);
			wp_cache_set( self::RUNNING_HEATMAP_IDS, $ids, self::CACHE_GROUP );
		}
		return $ids;
	}
}
