<?php
/**
 * This file contains a class for sending email notifications.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class sends email notifications.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */
class Nelio_AB_Testing_Mailer {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Mailer
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Mailer the single instance of this class.
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

		add_action( 'nab_start_experiment', array( $this, 'maybe_send_experiment_start_notification' ), 99 );
		add_action( 'nab_stop_experiment', array( $this, 'maybe_send_experiment_stop_notification' ), 99 );
	}//end init()

	/**
	 * Notifies that an experiment started, if conditions are met.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
	 *
	 * @since  5.0.0
	 */
	public function maybe_send_experiment_start_notification( $experiment ) {

		if ( 'enterprise' !== nab_get_subscription() ) {
			return;
		}//end if

		$settings = Nelio_AB_Testing_Settings::instance();
		if ( ! $settings->get( 'notify_experiment_start' ) ) {
			return;
		}//end if

		$recipients = $this->get_recipients();

		$initiator       = _x( 'WordPress Scheduler', 'text', 'nelio-ab-testing' );
		$starter_user_id = $experiment->get_starter();
		if ( 0 !== $starter_user_id ) {
			$starter_user = get_userdata( $starter_user_id );
			/* translators: 1: user name, 2. user email */
			$initiator  = sprintf( _x( '%1$s (%2$s)', 'text (email)', 'nelio-ab-testing' ), $starter_user->display_name, $starter_user->user_email );
			$recipients = array_diff( $recipients, array( $starter_user->user_email ) );
		}//end if

		$experiment_name = $experiment->get_name();
		$experiment_url  = $experiment->get_url();
		$end_mode        = $experiment->get_end_mode();
		$end_value       = $experiment->get_end_value();

		$experiment_start_date = $this->get_local_date( $experiment->get_start_date() );
		$experiment_start_time = $this->get_local_time( $experiment->get_start_date() );

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/utils/notifications/experiment-start.php';
		$message = ob_get_contents();
		ob_end_clean();

		$subject = sprintf(
			/* translators: 1: the name of the test, 2: the name of the site */
			esc_html_x( '[Nelio A/B Testing] Test “%1$s” is now running on site “%2$s”', 'text', 'nelio-ab-testing' ),
			$experiment_name,
			get_bloginfo()
		);

		$this->send_email_notification( $recipients, $subject, $message );
	}//end maybe_send_experiment_start_notification()

	/**
	 * Notifies that an experiment finished, if conditions are met.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
	 *
	 * @since  5.0.0
	 */
	public function maybe_send_experiment_stop_notification( $experiment ) {

		if ( 'enterprise' !== nab_get_subscription() ) {
			return;
		}//end if

		$settings = Nelio_AB_Testing_Settings::instance();
		if ( ! $settings->get( 'notify_experiment_stop' ) ) {
			return;
		}//end if

		$recipients = $this->get_recipients();

		$finalizer       = _x( 'WordPress Scheduler', 'text', 'nelio-ab-testing' );
		$stopper_user_id = $experiment->get_stopper();
		$stopper_user    = empty( $stopper_user_id ) ? false : get_userdata( $stopper_user_id );
		if ( ! empty( $stopper_user ) ) {
			/* translators: 1: user name, 2. user email */
			$finalizer  = sprintf( _x( '%1$s (%2$s)', 'text (email)', 'nelio-ab-testing' ), $stopper_user->display_name, $stopper_user->user_email );
			$recipients = array_diff( $recipients, array( $stopper_user->user_email ) );
		}//end if

		$experiment_name = $experiment->get_name();
		$experiment_url  = $experiment->get_url();
		$end_mode        = $experiment->get_end_mode();
		$end_value       = $experiment->get_end_value();

		$experiment_end_date = $this->get_local_date( $experiment->get_end_date() );
		$experiment_end_time = $this->get_local_time( $experiment->get_end_date() );

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/utils/notifications/experiment-stop.php';
		$message = ob_get_contents();
		ob_end_clean();

		$subject = sprintf(
			/* translators: 1: the name of the test, 2: the name of the site */
			esc_html_x( '[Nelio A/B Testing] Test “%1$s” finished on site “%2$s”', 'text', 'nelio-ab-testing' ),
			$experiment_name,
			get_bloginfo()
		);

		$this->send_email_notification( $recipients, $subject, $message );
	}//end maybe_send_experiment_stop_notification()

	/**
	 * Notifies that a subscription almost runned out of quota.
	 *
	 * @since  5.0.0
	 */
	public function send_almost_no_more_quota_notification() {

		$account_url = add_query_arg( 'page', 'nelio-ab-testing-account', admin_url( 'admin.php' ) );

		$recipients = $this->get_recipients();
		$subject    = esc_html_x( '[Nelio A/B Testing] The amount of quota on your subscription is low', 'text', 'nelio-ab-testing' );

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/utils/notifications/almost-no-more-quota.php';
		$message = ob_get_contents();
		ob_end_clean();
		$this->send_email_notification( $recipients, $subject, $message );
	}//end send_almost_no_more_quota_notification()

	/**
	 * Notifies that a site almost runned out of quota.
	 *
	 * @since  6.0.4
	 */
	public function send_almost_no_more_quota_in_site_notification() {

		$account_url = add_query_arg( 'page', 'nelio-ab-testing-account', admin_url( 'admin.php' ) );

		$recipients = $this->get_recipients();
		$subject    = esc_html_x( '[Nelio A/B Testing] The amount of quota on your site is low', 'text', 'nelio-ab-testing' );

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/utils/notifications/almost-no-more-quota-in-site.php';
		$message = ob_get_contents();
		ob_end_clean();
		$this->send_email_notification( $recipients, $subject, $message );
	}//end send_almost_no_more_quota_in_site_notification()

	/**
	 * Notifies that a subscription runned out of quota.
	 *
	 * @since  5.0.0
	 */
	public function send_no_more_quota_notification() {

		$account_url = add_query_arg( 'page', 'nelio-ab-testing-account', admin_url( 'admin.php' ) );

		$recipients = $this->get_recipients();
		$subject    = esc_html_x( '[Nelio A/B Testing] There is no more quota on your subscription', 'text', 'nelio-ab-testing' );

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/utils/notifications/no-more-quota.php';
		$message = ob_get_contents();
		ob_end_clean();
		$this->send_email_notification( $recipients, $subject, $message );
	}//end send_no_more_quota_notification()

	/**
	 * Notifies that a site runned out of quota.
	 *
	 * @since  6.0.4
	 */
	public function send_no_more_quota_in_site_notification() {

		$account_url = add_query_arg( 'page', 'nelio-ab-testing-account', admin_url( 'admin.php' ) );

		$recipients = $this->get_recipients();
		$subject    = esc_html_x( '[Nelio A/B Testing] There is no more quota on your site', 'text', 'nelio-ab-testing' );

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/utils/notifications/no-more-quota-in-site.php';
		$message = ob_get_contents();
		ob_end_clean();
		$this->send_email_notification( $recipients, $subject, $message );
	}//end send_no_more_quota_in_site_notification()

	public function nab_set_html_email_content_type() {
		return 'text/html';
	}//end nab_set_html_email_content_type()

	private function send_email_notification( $recipients, $subject, $message ) {

		if ( empty( $recipients ) ) {
			return;
		}//end if

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		foreach ( $recipients as $email_address ) {
			$headers[] = 'Bcc: ' . $email_address;
		}//end foreach

		$to = array();

		add_filter( 'wp_mail_content_type', array( $this, 'nab_set_html_email_content_type' ) );
		wp_mail( $to, $subject, $message, $headers ); // phpcs:ignore
		remove_filter( 'wp_mail_content_type', array( $this, 'nab_set_html_email_content_type' ) );
	}//end send_email_notification()

	private function get_recipients() {

		$recipients    = array();
		$settings      = Nelio_AB_Testing_Settings::instance();
		$stored_emails = $settings->get( 'notification_emails' );

		$exploded_emails = explode( '\n', $stored_emails );
		foreach ( $exploded_emails as $raw_email ) {
			$sanitized_email = sanitize_email( $raw_email );
			if ( ! empty( $sanitized_email ) ) {
				$recipients[] = $sanitized_email;
			}//end if
		}//end foreach

		return $recipients;
	}//end get_recipients()

	private function get_local_date( $utc_date ) {

		$date = new DateTime( $utc_date, new DateTimeZone( nab_get_timezone() ) );
		return date_i18n( get_option( 'date_format' ), strtotime( $date->format( 'U' ) ) );
	}//end get_local_date()

	private function get_local_time( $utc_date ) {

		$date = new DateTime( $utc_date, new DateTimeZone( nab_get_timezone() ) );
		return date_i18n( get_option( 'time_format' ), strtotime( $date->format( 'U' ) ) );
	}//end get_local_time()
}//end class
