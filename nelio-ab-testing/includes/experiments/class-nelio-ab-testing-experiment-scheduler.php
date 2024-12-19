<?php
/**
 * This file contains a class for dealing with the start and stop of experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class automatically starts and stops experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment_Scheduler {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Experiment_Scheduler
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Experiment_Scheduler the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'nab_save_experiment', array( $this, 'maybe_schedule_experiment' ), 99 );
		add_action( 'nab_start_experiment', array( $this, 'maybe_enqueue_experiment_finalization_task' ), 99 );
		add_action( 'nab_resume_experiment', array( $this, 'maybe_enqueue_experiment_finalization_task' ), 99 );
		add_action( 'nab_stop_experiment', array( $this, 'maybe_dequeue_experiment_finalization_task' ), 99 );
		add_action( 'nab_pause_experiment', array( $this, 'maybe_dequeue_experiment_finalization_task' ), 99 );

		add_action( 'nab_check_running_experiment', array( $this, 'maybe_stop_running_experiment' ) );
		add_action( 'nab_start_scheduled_experiment', array( $this, 'start_scheduled_experiment' ) );
	}//end init()

	/**
	 * Schedules the start of an experiment post using the WP Cron, if the status is scheduled.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @since  5.0.0
	 */
	public function maybe_schedule_experiment( $experiment ) {

		$start_date = $experiment->get_start_date();

		wp_clear_scheduled_hook( 'nab_start_scheduled_experiment', array( $experiment->get_id() ) );
		if ( 'scheduled' === $experiment->get_status() ) {
			wp_schedule_single_event( strtotime( $start_date ), 'nab_start_scheduled_experiment', array( $experiment->get_id() ) );
		}//end if
	}//end maybe_schedule_experiment()

	/**
	 * Schedules the task that terminates an experiment post using the WP Cron, if conditions are met.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @since  5.0.0
	 */
	public function maybe_enqueue_experiment_finalization_task( $experiment ) {

		$end_mode  = $experiment->get_end_mode();
		$end_value = $experiment->get_end_value();

		switch ( $end_mode ) {
			case 'duration':
				$days = $end_value;
				$time = strtotime( $experiment->get_start_date() );
				wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
				wp_schedule_single_event( $time + ( $days * DAY_IN_SECONDS ), 'nab_check_running_experiment', array( $experiment->get_id() ) );
				break;

			case 'page-views':
			case 'confidence':
				wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
				wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'nab_check_running_experiment', array( $experiment->get_id() ) );
				break;

			default:
				wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
				return;
		}//end switch
	}//end maybe_enqueue_experiment_finalization_task()

	/**
	 * Unschedules the task that terminates an experiment (if any) using the WP Cron, when the experiment stops.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @since  5.0.0
	 */
	public function maybe_dequeue_experiment_finalization_task( $experiment ) {

		wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
	}//end maybe_dequeue_experiment_finalization_task()

	public function maybe_stop_running_experiment( $post_id ) {

		$experiment = nab_get_experiment( $post_id );
		if ( 'running' !== $experiment->get_status() ) {
			return;
		}//end if

		$results = nab_get_experiment_results( $experiment->get_id() );

		$end_mode  = $experiment->get_end_mode();
		$end_value = $experiment->get_end_value();

		switch ( $end_mode ) {
			case 'duration':
				$this->stop_scheduled_experiment( $experiment );
				break;

			case 'page-views':
				$page_views          = $end_value;
				$consumed_page_views = $results->get_consumed_page_views();

				if ( $consumed_page_views >= $page_views ) {
					$this->stop_scheduled_experiment( $experiment );
				} else {
					$next_attempt = $this->compute_next_schedule_time( $experiment, $page_views, $consumed_page_views );
					wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
					wp_schedule_single_event( $next_attempt, 'nab_check_running_experiment', array( $experiment->get_id() ) );
				}//end if
				break;

			case 'confidence':
				$confidence         = $end_value;
				$current_confidence = $results->get_current_confidence();

				if ( $current_confidence >= $confidence ) {
					$this->stop_scheduled_experiment( $experiment );
				} else {
					$next_attempt = $this->compute_next_schedule_time( $experiment, $confidence, $current_confidence );
					wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
					wp_schedule_single_event( $next_attempt, 'nab_check_running_experiment', array( $experiment->get_id() ) );
				}//end if
				break;

			default:
				return;
		}//end switch
	}//end maybe_stop_running_experiment()

	public function start_scheduled_experiment( $post_id ) {

		$experiment = nab_get_experiment( $post_id );
		$experiment->set_starter( 'system' );
		$experiment->start();
	}//end start_scheduled_experiment()

	public function stop_scheduled_experiment( $experiment ) {

		$experiment->set_stopper( 'system' );
		$experiment->stop();
	}//end stop_scheduled_experiment()

	private function compute_next_schedule_time( $experiment, $value_to_reach, $current_value ) {

		$time_to_current_value = time() - strtotime( $experiment->get_start_date() );

		if ( 0 === $current_value ) {
			return time() + min( $time_to_current_value, DAY_IN_SECONDS );
		}//end if

		// current value  -> time to current value.
		// value to reach -> X ( forecasted time to value ).
		$forecasted_time_to_value = ( $value_to_reach * $time_to_current_value ) / $current_value;
		$time_diff                = $forecasted_time_to_value - $time_to_current_value;

		// Next time is between 15 minutes and 6 hours.
		return time() + min( max( $time_diff, MINUTE_IN_SECONDS * 15 ), HOUR_IN_SECONDS * 6 );
	}//end compute_next_schedule_time()
}//end class
