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
	 * @var TResults
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
	 * @param Nelio_AB_Testing_Experiment $experiment The experiment.
	 * @param TAWS_Experiment_Results     $results    Results object.
	 *
	 * @since  5.0.0
	 */
	private function __construct( $experiment, $results ) {
		$this->ID      = absint( $experiment->ID );
		$this->results = self::process_results( $experiment, $results );
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
			return $experiment; // @codeCoverageIgnore
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
		return array_sum( wp_list_pluck( $this->results['alternatives'], 'visits' ) );
	}

	/**
	 * Returns the current confidence of the experiment results.
	 *
	 * @return float the current confidence of the results
	 *
	 * @since  5.0.0
	 */
	public function get_current_confidence() {
		$winners = $this->results['winners'];
		$winner  = reset( $winners );
		return is_array( $winner ) ? (float) $winner['confidence'] : 0;
	}

	/**
	 * Processes the raw cloud response into the structure consumed by the
	 * JavaScript application.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 * @param TAWS_Experiment_Results     $results Raw cloud results.
	 * @return TResults Processed results.
	 */
	private static function process_results( $experiment, $results ) {
		$goal_results = self::as_array( $results['results'] ?? array() );
		return array(
			'winners'       => self::extract_winners( $goal_results, $experiment->get_goals() ),
			'wasEnoughData' => self::extract_data_availability( $goal_results, $experiment->get_goals() ),
			'alternatives'  => self::extract_alternatives( $results, $experiment ),
			'segments'      => self::process_segments( $results, $experiment ),
		) + self::process_unique_results( $results, $experiment );
	}

	/**
	 * Processes unique cloud results, when present.
	 *
	 * @param TAWS_Experiment_Results     $results Raw cloud results.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 * @return array{}|array{winnersUnique:array<string,TResults_Goal_Winner>,wasEnoughDataUnique:array<string,bool>} Processed unique results.
	 */
	private static function process_unique_results( $results, $experiment ) {
		if ( ! isset( $results['uniqueResults'] ) || ! is_array( $results['uniqueResults'] ) ) {
			return array();
		}

		return array(
			'winnersUnique'       => self::extract_winners( self::as_array( $results['uniqueResults'] ), $experiment->get_goals() ),
			'wasEnoughDataUnique' => self::extract_data_availability( self::as_array( $results['uniqueResults'] ), $experiment->get_goals() ),
		);
	}

	/**
	 * Extracts winner information from positional cloud goal results.
	 *
	 * @param array<string,mixed> $results Cloud goal results.
	 * @param list<TGoal>         $goals Goals.
	 * @return array<string,array{alternative:int,confidence:float}>
	 */
	private static function extract_winners( $results, $goals ) {
		$winners = array();
		foreach ( $goals as $index => $goal ) {
			$data = self::as_array( $results[ 'g' . $index ] ?? array() );
			if ( 'win' === ( $data['status'] ?? '' ) ) {
				$winners[ $goal['id'] ] = array(
					'alternative' => absint( $data['winner'] ?? 0 ),
					'confidence'  => absint( $data['confidence'] ?? 0 ),
				);
			}
		}
		return $winners;
	}

	/**
	 * Extracts data-availability flags from positional cloud goal results.
	 *
	 * @param array<string,mixed> $results Cloud goal results.
	 * @param list<TGoal>         $goals Goals.
	 * @return array<string,bool>
	 */
	private static function extract_data_availability( $results, $goals ) {
		$availability = array();
		foreach ( $goals as $index => $goal ) {
			$data                        = self::as_array( $results[ 'g' . $index ] ?? array() );
			$availability[ $goal['id'] ] = 'low-data' !== ( $data['status'] ?? '' );
		}
		return $availability;
	}

	/**
	 * Processes results for each configured segment.
	 *
	 * @param TAWS_Experiment_Results     $results Raw cloud results.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 * @return array<string,TResults_Segment_Data> Processed segment results.
	 */
	private static function process_segments( $results, $experiment ) {
		$processed = array();
		foreach ( $experiment->get_segments() as $index => $segment ) {
			$key                         = 's' . ( $index + 1 );
			$segments                    = self::as_array( $results['segments'] ?? array() );
			$data                        = self::as_array( $segments[ $key ] ?? array() );
			$segment_results             = self::as_array( $data['results'] ?? array() );
			$processed[ $segment['id'] ] = array(
				'winners'       => self::extract_winners( $segment_results, $experiment->get_goals() ),
				'wasEnoughData' => self::extract_data_availability( $segment_results, $experiment->get_goals() ),
				'alternatives'  => self::extract_alternatives( $data, $experiment ),
				'id'            => $segment['id'],
			) + ( isset( $data['uniqueResults'] )
				? array(
					'winnersUnique'       => self::extract_winners( self::as_array( $data['uniqueResults'] ), $experiment->get_goals() ),
					'wasEnoughDataUnique' => self::extract_data_availability( self::as_array( $data['uniqueResults'] ), $experiment->get_goals() ),
				)
				: array() );
		}
		return $processed;
	}

	/**
	 * Processes the alternatives in a cloud result set.
	 *
	 * @param array<string,mixed>         $results Cloud result set.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 * @return array<string,TResults_Alternative_Tracking_Data> Processed alternative results.
	 */
	private static function extract_alternatives( $results, $experiment ) {
		$processed = array(
			'control' => self::alternative_data( array(), 'control', '', '', array() ),
		);
		$end_date  = 'finished' === $experiment->get_status() ? $experiment->get_end_date() : current_time( 'c' );
		foreach ( $experiment->get_alternatives() as $index => $alternative ) {
			$processed[ $alternative['id'] ] = self::alternative_data(
				self::as_array( $results[ 'a' . $index ] ?? array() ),
				$alternative['id'],
				(string) $experiment->get_start_date(),
				(string) $end_date,
				$experiment->get_goals()
			);
		}
		return $processed;
	}

	/**
	 * Processes one alternative's metrics and timeline.
	 *
	 * @param array<string,mixed> $data Cloud alternative results.
	 * @param string              $id Alternative ID.
	 * @param string              $start_date Start date.
	 * @param string              $end_date End date.
	 * @param list<TGoal>         $goals Goals.
	 * @return TResults_Alternative_Tracking_Data Processed alternative results.
	 */
	private static function alternative_data( $data, $id, $start_date, $end_date, $goals ) {
		$data = self::as_array( $data );
		return array(
			'id'                       => $id,
			'visits'                   => self::number( $data['v'] ?? 0 ),
			'values'                   => self::goal_values( self::as_array( $data['cv'] ?? array() ), $goals ),
			'conversions'              => self::goal_values( self::as_array( $data['c'] ?? array() ), $goals ),
			'conversionRates'          => self::goal_rates( self::as_array( $data['cr'] ?? array() ), $goals ),
			'improvementFactors'       => self::goal_rates( self::as_array( $data['i'] ?? array() ), $goals ),

			'uniqueVisits'             => self::number( $data['uv'] ?? 0 ),
			'uniqueValues'             => self::goal_values( self::as_array( $data['ucv'] ?? array() ), $goals ),
			'uniqueConversions'        => self::goal_values( self::as_array( $data['uc'] ?? array() ), $goals ),
			'uniqueConversionRates'    => self::goal_rates( self::as_array( $data['ucr'] ?? array() ), $goals ),
			'uniqueImprovementFactors' => self::goal_rates( self::as_array( $data['ui'] ?? array() ), $goals ),

			'timeline'                 => self::timeline( self::as_array( $data['t'] ?? array() ), $start_date, $end_date, $goals ),
		);
	}

	/**
	 * Maps a positional cloud metric to goal IDs.
	 *
	 * @param array<string,mixed> $values Positional metric values.
	 * @param list<TGoal>         $goals Goals.
	 * @return array<string,int|float>
	 */
	private static function goal_values( $values, $goals ) {
		$values = self::as_array( $values );
		$result = array();
		foreach ( $goals as $index => $goal ) {
			$value                 = $values[ 'g' . $index ] ?? 0;
			$result[ $goal['id'] ] = is_int( $value ) || is_float( $value ) ? $value : ( is_numeric( $value ) ? (float) $value : 0 );
		}
		return $result;
	}

	/**
	 * Maps and rounds a positional cloud rate to goal IDs.
	 *
	 * @param array<string,mixed> $values Positional metric values.
	 * @param list<TGoal>         $goals Goals.
	 * @return array<string,float>
	 */
	private static function goal_rates( $values, $goals ) {
		$values = self::as_array( $values );
		$result = array();
		foreach ( $goals as $index => $goal ) {
			$value                 = $values[ 'g' . $index ] ?? 0;
			$result[ $goal['id'] ] = round( is_numeric( $value ) ? (float) $value : 0, 2 );
		}
		return $result;
	}

	/**
	 * Processes the daily results for an alternative.
	 *
	 * @param array<string,mixed> $timeline Cloud timeline.
	 * @param string              $start_date Start date.
	 * @param string              $end_date End date.
	 * @param list<TGoal>         $goals Goals.
	 * @return list<TResults_Day> Processed timeline.
	 */
	private static function timeline( $timeline, $start_date, $end_date, $goals ) {
		if ( empty( $start_date ) || empty( $end_date ) ) {
			return array();
		}

		$start = new DateTimeImmutable( $start_date, new DateTimeZone( nab_get_timezone() ) );
		$end   = new DateTimeImmutable( $end_date, new DateTimeZone( nab_get_timezone() ) );
		$end   = $end->format( 'Y-m-d' );

		$result = array();
		$day    = $start;
		while ( $day->format( 'Y-m-d' ) <= $end ) {
			$date = $day->format( 'Y-m-d' );
			$data = array();
			foreach ( $timeline as $entry ) {
				$entry = self::as_array( $entry );
				if ( ( $entry['d'] ?? '' ) === $date ) {
					$data = $entry;
					break;
				}
			}
			$result[] = array(
				'date'              => $date,
				'visits'            => self::number( $data['v'] ?? 0 ),
				'conversions'       => self::goal_values( $data, $goals ),
				'values'            => self::timeline_values( $data, 'val', $goals ),
				'uniqueVisits'      => self::number( $data['uv'] ?? 0 ),
				'uniqueConversions' => self::timeline_values( $data, 'ug', $goals ),
				'uniqueValues'      => self::timeline_values( $data, 'uval', $goals ),
			);
			$day      = $day->modify( '+1 day' );
		}
		return $result;
	}

	/**
	 * Maps timeline values to goal IDs.
	 *
	 * @param array<string,mixed> $data Daily cloud results.
	 * @param string              $prefix Metric prefix.
	 * @param list<TGoal>         $goals Goals.
	 * @return array<string,int|float>
	 */
	private static function timeline_values( $data, $prefix, $goals ) {
		$data   = self::as_array( $data );
		$result = array();
		foreach ( $goals as $index => $goal ) {
			$value                 = $data[ $prefix . $index ] ?? 0;
			$result[ $goal['id'] ] = is_int( $value ) || is_float( $value ) ? $value : ( is_numeric( $value ) ? (float) $value : 0 );
		}
		return $result;
	}

	/**
	 * Returns a numeric result value or zero.
	 *
	 * @param mixed $value Value.
	 * @return int|float Numeric value.
	 */
	private static function number( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			return $value;
		}
		return is_numeric( $value ) ? (float) $value : 0;
	}

	/**
	 * Converts an unknown value into an array.
	 *
	 * @param mixed $value Value.
	 * @return array<string,mixed> Array value.
	 */
	private static function as_array( $value ) {
		if ( ! is_array( $value ) ) {
			return array(); // @codeCoverageIgnore
		}
		$keys   = array_map( 'strval', array_keys( $value ) );
		$values = array_values( $value );
		return array_combine( $keys, $values );
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
