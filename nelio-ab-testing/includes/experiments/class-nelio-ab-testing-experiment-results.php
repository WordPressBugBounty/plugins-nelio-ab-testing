<?php
/**
 * This file defines the class of the results of a Nelio A/B Testing Experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Results of an Experiment in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment_Results {

	/**
	 * The experiment results.
	 *
	 * @var TAWS_Experiment_Results|null
	 */
	public $results;

	/**
	 * The experiment (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post|Nelio_AB_Testing_Experiment $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 * @param TAWS_Experiment_Results                     $results Results object.
	 *
	 * @since  5.0.0
	 */
	private function __construct( $experiment, $results ) {
		if ( isset( $experiment->ID ) ) {
			$this->ID      = absint( $experiment->ID );
			$this->results = $results;
		}
	}

	/**
	 * Retrieves the experiment results from Nelio’s cloud.
	 *
	 * @param Nelio_AB_Testing_Experiment|int $experiment the experiment or its ID.
	 *
	 * @return WP_Error|Nelio_AB_Testing_Experiment_Results the results.
	 */
	public static function get_experiment_results( $experiment ) {

		if ( ! $experiment instanceof Nelio_AB_Testing_Experiment ) {
			$experiment = nab_get_experiment( absint( $experiment ) );
		}

		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$were_results_definitive = get_post_meta( $experiment->ID, '_nab_are_timeline_results_definitive', true );
		$were_results_definitive = ! empty( $were_results_definitive );
		if ( $were_results_definitive && 'finished' === $experiment->get_status() ) {
			$results = get_post_meta( $experiment->ID, '_nab_timeline_results', true );
			if ( ! empty( $results ) ) {
				return new Nelio_AB_Testing_Experiment_Results( $experiment, $results );
			}
		}

		$results = self::get_results_from_cloud( $experiment );
		if ( is_wp_error( $results ) ) {
			return $results;
		}

		update_post_meta( $experiment->ID, '_nab_timeline_results', $results );

		$are_results_definitive = 'finished' === $experiment->get_status();
		if ( $are_results_definitive ) {
			update_post_meta( $experiment->ID, '_nab_are_timeline_results_definitive', true );
		} else {
			delete_post_meta( $experiment->ID, '_nab_are_timeline_results_definitive' );
		}

		return new Nelio_AB_Testing_Experiment_Results( $experiment, $results );
	}

	/**
	 * Returns the ID of this experiment.
	 *
	 * @return integer the ID of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_id() {

		return $this->ID;
	}

	/**
	 * Returns the consumed page views for the experiment.
	 *
	 * @return int the consumed page views
	 *
	 * @since  5.0.0
	 */
	public function get_consumed_page_views() {

		$results = $this->results;
		if ( empty( $results ) ) {
			return 0;
		}

		$page_views = 0;

		foreach ( $results as $key => $value ) {
			if ( 'a' !== $key[0] || ! isset( $value['v'] ) ) {
				continue;
			}

			$page_views += $value['v'];
		}

		return $page_views;
	}

	/**
	 * Returns the current confidence of the experiment results.
	 *
	 * @return float the current confidence of the results
	 *
	 * @since  5.0.0
	 */
	public function get_current_confidence() {

		$results = $this->results;
		if ( empty( $results ) ) {
			return 0;
		}

		if ( ! isset( $results['results'] ) ) {
			return 0;
		}

		$results_value = $results['results'];
		if ( ! isset( $results_value['g0'] ) ) {
			return 0;
		}

		$main_goal = $results_value['g0'];
		if ( ! isset( $main_goal['confidence'] ) ) {
			return 0;
		} else {
			return $main_goal['confidence'];
		}
	}

	/**
	 * Gets experiment resutls from cloud.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return TAWS_Experiment_Results|WP_Error
	 */
	private static function get_results_from_cloud( $experiment ) {

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/experiment/' . $experiment->get_id(), 'wp' );
		$url      = self::add_dates_in_url( $url, $experiment );
		$url      = self::add_segments_in_url( $url, $experiment );
		$response = wp_remote_request( $url, $data );

		/** @var TAWS_Experiment_Results|WP_Error */
		return nab_extract_response_body( $response );
	}

	/**
	 * Adds experiment start/end params to the URL.
	 *
	 * @param string                      $url URL.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return string
	 */
	private static function add_dates_in_url( $url, $experiment ) {
		$start = $experiment->get_start_date();
		if ( is_string( $start ) ) {
			$url = add_query_arg( 'start', rawurlencode( $start ), $url );
		}

		$end = $experiment->get_end_date();
		if ( is_string( $end ) && 'finished' === $experiment->get_status() ) {
			$url = add_query_arg( 'end', rawurlencode( $end ), $url );
		}

		return add_query_arg( 'tz', rawurlencode( nab_get_timezone() ), $url );
	}

	/**
	 * Adds experiment segments in the given URL.
	 *
	 * @param string                      $url URL.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return string
	 */
	private static function add_segments_in_url( $url, $experiment ) {
		$segments = $experiment->get_segments();
		$segments = ! empty( $segments ) ? $segments : array();
		return add_query_arg( 'segments', count( $segments ), $url );
	}
}
