<?php
/**
 * This file contains a class for checking the quota periodically.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class checks the quota periodically.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */
class Nelio_AB_Testing_Quota_Checker {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'nab_start_experiment', array( $this, 'check_quota' ), 99 );
		add_action( 'nab_check_quota', array( $this, 'check_quota' ) );
	}

	/**
	 * Callback to check quota.
	 *
	 * @return void
	 */
	public function check_quota() {

		if ( 'professional' !== nab_get_subscription() && 'enterprise' !== nab_get_subscription() ) {
			return; // @codeCoverageIgnore
		}

		$settings                    = Nelio_AB_Testing_Settings::instance();
		$notify_no_more_quota        = $settings->get( 'notify_no_more_quota' );
		$notify_almost_no_more_quota = $settings->get( 'notify_almost_no_more_quota' );

		if ( ! $notify_no_more_quota && ! $notify_almost_no_more_quota ) {
			return; // @codeCoverageIgnore
		}

		$quota_data = $this->get_quota();
		if ( empty( $quota_data ) ) {
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS ); // @codeCoverageIgnore
			return; // @codeCoverageIgnore
		}

		$quota_mode       = $quota_data['mode'];
		$available_quota  = $quota_data['availableQuota'];
		$quota_percentage = $quota_data['percentage'];

		$last_quota_notification_sent = get_option( 'nab_last_quota_notification_sent', '' );

		// Notify no quota.
		if ( 0 === $available_quota && $notify_no_more_quota && 'no_more_quota' !== $last_quota_notification_sent ) {
			$mailer = new Nelio_AB_Testing_Mailer();
			if ( 'site' === $quota_mode ) {
				$mailer->send_no_more_quota_in_site_notification();
			} else {
				$mailer->send_no_more_quota_notification();
			}
			update_option( 'nab_last_quota_notification_sent', 'no_more_quota' );
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;
		}

		// Notify almost no quota.
		if ( 0 < $quota_percentage && $quota_percentage < 20 && $notify_almost_no_more_quota && 'almost_no_more_quota' !== $last_quota_notification_sent ) {
			$mailer = new Nelio_AB_Testing_Mailer();
			if ( 'site' === $quota_mode ) {
				$mailer->send_almost_no_more_quota_in_site_notification();
			} else {
				$mailer->send_almost_no_more_quota_notification();
			}
			update_option( 'nab_last_quota_notification_sent', 'almost_no_more_quota' );
			$this->maybe_schedule_next_quota_check( time() + HOUR_IN_SECONDS );
			return;
		}

		// Reset option for last quota notification sent.
		if ( 'no_more_quota' === $last_quota_notification_sent && 0 < $available_quota ) {
			delete_option( 'nab_last_quota_notification_sent' );
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;
		}

		// Reset option for last quota notification sent.
		if ( 'almost_no_more_quota' === $last_quota_notification_sent && 20 <= $quota_percentage ) {
			delete_option( 'nab_last_quota_notification_sent' );
			$this->maybe_schedule_next_quota_check( time() + HOUR_IN_SECONDS );
			return;
		}
	}

	/**
	 * Retrieves available quota from Nelio’s cloud, or `false` if it couldn’t get it.
	 *
	 * @return array{mode:'site'|'subscription', availableQuota:int, percentage:int}|false
	 */
	private function get_quota() {
		$helper   = new Nelio_AB_Testing_Account_REST_Controller();
		$response = $helper->get_site_quota();
		return ! is_wp_error( $response ) ? $response : false;
	}

	/**
	 * Schedules the next quota check if there are any experiments running.
	 *
	 * @param int $next_check_time Next check time.
	 *
	 * @return void
	 */
	private function maybe_schedule_next_quota_check( $next_check_time ) {
		if ( nab_are_there_experiments_running() ) {
			wp_schedule_single_event( $next_check_time, 'nab_check_quota' );
		}
	}
}
