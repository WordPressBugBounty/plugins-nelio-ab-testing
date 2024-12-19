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
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Quota_Checker
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Quota_Checker the single instance of this class.
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

		add_action( 'nab_start_experiment', array( $this, 'check_quota' ), 99 );
		add_action( 'nab_check_quota', array( $this, 'check_quota' ) );
	}//end init()

	public function check_quota() {

		if ( 'professional' !== nab_get_subscription() && 'enterprise' !== nab_get_subscription() ) {
			return;
		}//end if

		if ( ! current_user_can( 'edit_nab_experiments' ) ) {
			return;
		}//end if

		$settings                    = Nelio_AB_Testing_Settings::instance();
		$notify_no_more_quota        = $settings->get( 'notify_no_more_quota' );
		$notify_almost_no_more_quota = $settings->get( 'notify_almost_no_more_quota' );

		if ( ! $notify_no_more_quota && ! $notify_almost_no_more_quota ) {
			return;
		}//end if

		$quota_data = $this->get_quota();
		if ( empty( $quota_data ) ) {
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;
		}//end if

		$quota_mode       = nab_array_get( $quota_data, 'mode', 'subscription' );
		$available_quota  = nab_array_get( $quota_data, 'availableQuota', 0 );
		$quota_percentage = nab_array_get( $quota_data, 'percentage', 0 );

		$last_quota_notification_sent = get_option( 'nab_last_quota_notification_sent' );

		// Notify no quota.
		if ( 0 === $available_quota && $notify_no_more_quota && 'no_more_quota' !== $last_quota_notification_sent ) {
			$mailer = Nelio_AB_Testing_Mailer::instance();
			if ( 'site' === $quota_mode ) {
				$mailer->send_no_more_quota_in_site_notification();
			} else {
				$mailer->send_no_more_quota_notification();
			}//end if
			update_option( 'nab_last_quota_notification_sent', 'no_more_quota' );
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;
		}//end if

		// Notify almost no quota.
		if ( $quota_percentage < 20 && $notify_almost_no_more_quota && 'almost_no_more_quota' !== $last_quota_notification_sent ) {
			$mailer = Nelio_AB_Testing_Mailer::instance();
			if ( 'site' === $quota_mode ) {
				$mailer->send_almost_no_more_quota_in_site_notification();
			} else {
				$mailer->send_almost_no_more_quota_notification();
			}//end if
			update_option( 'nab_last_quota_notification_sent', 'almost_no_more_quota' );
			$this->maybe_schedule_next_quota_check( time() + HOUR_IN_SECONDS );
			return;
		}//end if

		// Reset option for last quota notification sent.
		if ( 'no_more_quota' === $last_quota_notification_sent && 0 < $available_quota ) {
			delete_option( 'nab_last_quota_notification_sent' );
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;
		}//end if

		// Reset option for last quota notification sent.
		if ( 'almost_no_more_quota' === $last_quota_notification_sent && 20 <= $quota_percentage ) {
			delete_option( 'nab_last_quota_notification_sent' );
			$this->maybe_schedule_next_quota_check( time() + HOUR_IN_SECONDS );
			return;
		}//end if
	}//end check_quota()

	private function get_quota() {
		$request  = new WP_REST_Request( 'GET', '/nab/v1/site/quota' );
		$response = rest_do_request( $request );
		return $this->is_valid_response( $response )
			? $response->get_data()
			: false;
	}//end get_quota()

	private function is_valid_response( $response ) {
		return 200 <= $response->status && $response->status < 300;
	}//end is_valid_response()

	private function maybe_schedule_next_quota_check( $next_check_time ) {
		if ( nab_are_there_experiments_running() ) {
			wp_schedule_single_event( $next_check_time, 'nab_check_quota' );
		}//end if
	}//end maybe_schedule_next_quota_check()
}//end class
