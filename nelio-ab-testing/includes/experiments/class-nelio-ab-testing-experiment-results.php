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
	 * @var array
	 */
	public $results = null;

	/**
	 * The experiment (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 * @param array           $results Results object.
	 *
	 * @since  5.0.0
	 */
	private function __construct( $experiment, $results ) {
		if ( isset( $experiment->ID ) ) {
			$this->ID      = absint( $experiment->ID );
			$this->results = $results;
		}//end if
	}//end __construct()

	/**
	 * Retrieves the experiment results from Nelio’s cloud.
	 *
	 * @param Nelio_AB_Testing_Experiment|int $experiment the experiment or its ID.
	 *
	 * return WP_Error|Nelio_AB_Testing_Experiment_Results the results.
	 */
	public static function get_experiment_results( $experiment ) {

		if ( ! $experiment instanceof Nelio_AB_Testing_Experiment ) {
			$experiment = nab_get_experiment( absint( $experiment ) );
		}//end if

		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}//end if

		$were_results_definitive = get_post_meta( $experiment->ID, '_nab_are_timeline_results_definitive', true );
		$were_results_definitive = ! empty( $were_results_definitive );
		if ( $were_results_definitive && 'finished' === $experiment->get_status() ) {
			$results = get_post_meta( $experiment->ID, '_nab_timeline_results', true );
			return new Nelio_AB_Testing_Experiment_Results( $experiment, $results );
		}//end if

		$results = self::get_results_from_cloud( $experiment );
		if ( is_wp_error( $results ) ) {
			return $results;
		}//end if

		update_post_meta( $experiment->ID, '_nab_timeline_results', $results );

		$are_results_definitive = 'finished' === $experiment->get_status();
		if ( $are_results_definitive ) {
			update_post_meta( $experiment->ID, '_nab_are_timeline_results_definitive', true );
		} else {
			delete_post_meta( $experiment->ID, '_nab_are_timeline_results_definitive' );
		}//end if

		return new Nelio_AB_Testing_Experiment_Results( $experiment, $results );
	}//end get_experiment_results()

	/**
	 * Returns the ID of this experiment.
	 *
	 * @return integer the ID of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_id() {

		return $this->ID;
	}//end get_id()

	/**
	 * Returns the consumed page views for the experiment.
	 *
	 * @return int the consumed page views
	 *
	 * @since  5.0.0
	 */
	public function get_consumed_page_views() {

		$results = $this->results;
		if ( is_wp_error( $results ) || empty( $results ) ) {
			return 0;
		}//end if

		$page_views = 0;

		foreach ( $results as $key => $value ) {
			if ( 'a' !== $key[0] ) {
				continue;
			}//end if

			$page_views += $value['v'];
		}//end foreach

		return $page_views;
	}//end get_consumed_page_views()

	/**
	 * Returns the current confidence of the experiment results.
	 *
	 * @return float the current confidence of the results
	 *
	 * @since  5.0.0
	 */
	public function get_current_confidence() {

		$results = $this->results;
		if ( is_wp_error( $results ) || empty( $results ) ) {
			return 0;
		}//end if

		if ( ! isset( $results['results'] ) ) {
			return 0;
		}//end if

		$results_value = $results['results'];
		if ( ! isset( $results_value['g0'] ) ) {
			return 0;
		}//end if

		$main_goal = $results_value['g0'];
		if ( ! isset( $main_goal['confidence'] ) ) {
			return 0;
		} else {
			return $main_goal['confidence'];
		}//end if
	}//end get_current_confidence()

	private static function get_results_from_cloud( $experiment ) {

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
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

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return json_decode( $response['body'], true );
	}//end get_results_from_cloud()

	private static function add_dates_in_url( $url, $experiment ) {

		$url = add_query_arg( 'start', rawurlencode( $experiment->get_start_date() ), $url );
		if ( 'finished' === $experiment->get_status() ) {
			$url = add_query_arg( 'end', rawurlencode( $experiment->get_end_date() ), $url );
		}//end if

		$url = add_query_arg( 'tz', rawurlencode( nab_get_timezone() ), $url );

		return $url;
	}//end add_dates_in_url()

	private static function add_segments_in_url( $url, $experiment ) {

		$segments = $experiment->get_segments();
		$segments = ! empty( $segments ) ? $segments : array();

		$url = add_query_arg( 'segments', count( $segments ), $url );

		return $url;
	}//end add_segments_in_url()
}//end class
