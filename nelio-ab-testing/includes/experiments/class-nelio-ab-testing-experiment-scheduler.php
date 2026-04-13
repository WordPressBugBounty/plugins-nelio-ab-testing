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
	 * Hooks into WordPress.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function init() {
		add_action( 'nab_save_experiment', array( $this, 'maybe_schedule_experiment' ), 99 );
		add_action( 'nab_start_experiment', array( $this, 'maybe_enqueue_experiment_finalization_task' ), 99 );
		add_action( 'nab_resume_experiment', array( $this, 'maybe_enqueue_experiment_finalization_task' ), 99 );
		add_action( 'nab_stop_experiment', array( $this, 'maybe_dequeue_experiment_finalization_task' ), 99 );
		add_action( 'nab_pause_experiment', array( $this, 'maybe_dequeue_experiment_finalization_task' ), 99 );

		add_action( 'nab_start_scheduled_experiment', array( $this, 'start_scheduled_experiment' ) );
		add_action( 'nab_check_running_experiment', array( $this, 'maybe_stop_running_experiment' ) );
	}

	/**
	 * Schedules the start of an experiment post using the WP Cron, if the status is scheduled.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function maybe_schedule_experiment( $experiment ) {
		$start_date = $experiment->get_start_date();
		wp_clear_scheduled_hook( 'nab_start_scheduled_experiment', array( $experiment->get_id() ) );
		if ( 'scheduled' === $experiment->get_status() ) {
			assert( false !== $start_date );
			$start_date = strtotime( $start_date );
			if ( false !== $start_date ) {
				wp_schedule_single_event( $start_date, 'nab_start_scheduled_experiment', array( $experiment->get_id() ) );
			}
		}
	}

	/**
	 * Schedules the task that terminates an experiment post using the WP Cron, if conditions are met.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function maybe_enqueue_experiment_finalization_task( $experiment ) {
		$start_date = $experiment->get_start_date();
		assert( false !== $start_date );

		$end_mode  = $experiment->get_end_mode();
		$end_value = $experiment->get_end_value();

		switch ( $end_mode ) {
			case 'duration':
				$days = $end_value;
				$time = strtotime( $start_date );
				wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
				wp_schedule_single_event( $time + ( $days * DAY_IN_SECONDS ), 'nab_check_running_experiment', array( $experiment->get_id() ) );
				break;

			case 'page-views':
			case 'confidence':
				wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
				wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'nab_check_running_experiment', array( $experiment->get_id() ) );
				break;

			default:
				$this->maybe_dequeue_experiment_finalization_task( $experiment );
				return;
		}
	}

	/**
	 * Unschedules the task that terminates an experiment (if any) using the WP Cron, when the experiment stops.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function maybe_dequeue_experiment_finalization_task( $experiment ) {
		wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
	}

	/**
	 * Callback to stop a running experiment if it’s set to autostop and the condition has been met.
	 * If the condition hasn’t been met yet, it schedules a single event to check it in the future.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function maybe_stop_running_experiment( $post_id ) {

		$experiment = nab_get_experiment( $post_id );
		if ( is_wp_error( $experiment ) ) {
			return; // @codeCoverageIgnore
		}

		$start_date = $experiment->get_start_date();
		if ( 'running' !== $experiment->get_status() || false === $start_date ) {
			return; // @codeCoverageIgnore
		}

		$end_mode  = $experiment->get_end_mode();
		$end_value = $experiment->get_end_value();

		if ( 'manual' === $end_mode ) {
			return;
		}

		$results = nab_get_experiment_results( $experiment->get_id() );
		if ( is_wp_error( $results ) ) {
			return; // @codeCoverageIgnore
		}

		$auto_stop      = false;
		$expected_value = 0;
		$actual_value   = 0;
		if ( 'duration' === $end_mode ) {
			$auto_stop = true;
		} elseif ( 'page-views' === $end_mode ) {
			$expected_value = $end_value;
			$actual_value   = $results->get_consumed_page_views();
			$auto_stop      = $actual_value >= $expected_value;
		} elseif ( 'confidence' === $end_mode ) {
			$expected_value = $end_value;
			$actual_value   = $results->get_current_confidence();
			$auto_stop      = $actual_value >= $expected_value;
		}

		if ( $auto_stop ) {
			$this->auto_stop_experiment( $experiment );
		} else {
			$next_attempt = $this->compute_next_schedule_time( $start_date, $expected_value, $actual_value );
			wp_clear_scheduled_hook( 'nab_check_running_experiment', array( $experiment->get_id() ) );
			wp_schedule_single_event( $next_attempt, 'nab_check_running_experiment', array( $experiment->get_id() ) );
		}
	}

	/**
	 * Callback to start a scheduled test.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function start_scheduled_experiment( $post_id ) {
		$experiment = nab_get_experiment( $post_id );
		if ( is_wp_error( $experiment ) ) {
			return; // @codeCoverageIgnore
		}

		if ( 'scheduled' !== $experiment->get_status() ) {
			return; // @codeCoverageIgnore
		}

		$experiment->set_starter( 'system' );
		$experiment->start();
	}

	/**
	 * Stops the experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return void
	 */
	private function auto_stop_experiment( $experiment ) {
		if ( 'running' !== $experiment->get_status() ) {
			return; // @codeCoverageIgnore
		}
		$experiment->set_stopper( 'system' );
		$experiment->stop();
	}

	/**
	 * Computes the next schedule time when we should check if the (new) value has already reached the target.
	 *
	 * @param string    $start_date Experiment’s start date.
	 * @param int|float $value_to_reach Value to reach.
	 * @param int|float $current_value  Current value.
	 *
	 * @return int
	 */
	private function compute_next_schedule_time( $start_date, $value_to_reach, $current_value ) {
		$time_to_current_value = time() - strtotime( $start_date );

		// Rule of three:
		// current value  -> time to current value.
		// value to reach -> X ( forecasted time to value ).
		$forecasted_time_to_value = ( $value_to_reach * $time_to_current_value ) / max( 1, $current_value );
		$time_diff                = $forecasted_time_to_value - $time_to_current_value;

		// Next time is between 15 minutes and 6 hours.
		return (int) ceil( time() + min( max( $time_diff, MINUTE_IN_SECONDS * 15 ), HOUR_IN_SECONDS * 6 ) );
	}
}
