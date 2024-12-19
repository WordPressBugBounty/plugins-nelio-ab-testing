<?php
/**
 * This file contains a class for logging experiments in AWS.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class logs experiments in AWS.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */
class Nelio_AB_Testing_Logger {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Logger
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Logger the single instance of this class.
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

		add_action( 'nab_stop_experiment', array( $this, 'log_experiment' ), 99 );
		add_action( 'nab_updated', array( $this, 'log_site' ) );
		add_action( 'nab_site_created', array( $this, 'log_site' ) );
		add_action( 'upgrader_process_complete', array( $this, 'maybe_log_site_on_upgrade' ), 10, 2 );
		add_action( 'update_option_home', array( $this, 'maybe_log_site_on_option_update' ), 10, 2 );
		add_action( 'update_option_timezone_string', array( $this, 'maybe_log_site_on_option_update' ), 10, 2 );
		add_action( 'update_option_gmt_offset', array( $this, 'maybe_log_site_on_option_update' ), 10, 2 );
		add_action( 'update_option_WPLANG', array( $this, 'maybe_log_site_on_option_update' ), 10, 2 );
	}//end init()

	/**
	 * Logs an experiment, when the experiment stops.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the post object.
	 *
	 * @since  5.0.0
	 */
	public function log_experiment( $experiment ) {

		$params = array(
			'id'           => $experiment->get_id(),
			'type'         => $experiment->get_type(),
			'name'         => $experiment->get_name(),
			'description'  => $experiment->get_description(),
			'start'        => $experiment->get_start_date(),
			'end'          => $experiment->get_end_date(),
			'endMode'      => $experiment->get_end_mode(),
			'endValue'     => $experiment->get_end_value(),
			'starter'      => $experiment->get_starter(),
			'stopper'      => $experiment->get_stopper(),
			'alternatives' => count( $experiment->get_alternatives() ),
			'goals'        => count( $experiment->get_goals() ),
			'url'          => home_url(),
			'language'     => nab_get_language(),
			'timezone'     => nab_get_timezone(),
			'wpVersion'    => get_bloginfo( 'version' ),
			'nabVersion'   => nelioab()->plugin_version,
		);

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( $params ),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/experiment', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if
	}//end log_experiment()

	/**
	 * Logs the site.
	 *
	 * @since  5.0.0
	 */
	public function log_site() {

		if ( ! nab_get_site_id() ) {
			return;
		}//end if

		$params = array(
			'url'        => home_url(),
			'language'   => nab_get_language(),
			'timezone'   => nab_get_timezone(),
			'nabVersion' => nelioab()->plugin_version,
			'phpVersion' => preg_replace( '/-.*$/', '', phpversion() ),
			'wpVersion'  => get_bloginfo( 'version' ),
		);

		$data = array(
			'method'    => 'PUT',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( $params ),
		);

		$url = nab_get_api_url( '/site/' . nab_get_site_id(), 'wp' );
		wp_remote_request( $url, $data );
	}//end log_site()

	public function maybe_log_site_on_upgrade( $upgrader_object, $options ) {

		if ( 'update' !== $options['action'] || 'core' !== $options['type'] ) {
			return;
		}//end if

		$this->log_site();
	}//end maybe_log_site_on_upgrade()

	public function maybe_log_site_on_option_update( $old_value, $value ) {

		if ( $old_value === $value ) {
			return;
		}//end if

		$this->log_site();
	}//end maybe_log_site_on_option_update()
}//end class
