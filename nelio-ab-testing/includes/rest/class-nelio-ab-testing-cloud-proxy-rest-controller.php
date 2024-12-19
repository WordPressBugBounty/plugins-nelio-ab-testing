<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing cloud forwarding settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      6.1.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Cloud_Proxy_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  6.1.0
	 * @var    Nelio_AB_Testing_Cloud_Proxy_REST_Controller
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Cloud_Proxy_REST_Controller the single instance of this class.
	 *
	 * @since  6.1.0
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
	 * @since  6.1.0
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/domain/check',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'check_domain' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/domain/reset',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reset_proxy' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);
	}//end register_routes()

	/**
	 * Checks and manages the domain forwarding status.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function check_domain( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['domain'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Domain is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		if ( ! isset( $parameters['domainStatus'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Domain status is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$domain        = trim( sanitize_text_field( $parameters['domain'] ) );
		$domain_status = trim( sanitize_text_field( $parameters['domainStatus'] ) );

		switch ( $domain_status ) {

			case 'disabled':
			case 'missing-forward':
			case 'cert-validation-pending':
				return $this->check_certificate_status( $domain );

			case 'cert-validation-success':
				return $this->create_domain_forwarding();

			case 'success':
				return new WP_REST_Response(
					array( 'status' => 'success' ),
					200
				);

		}//end switch
	}//end check_domain()

	/**
	 * Resets the domain forwarding settings.
	 *
	 * @return WP_REST_Response The response
	 */
	public function reset_proxy() {

		$data = array(
			'method'    => 'DELETE',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/domain', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );
	}//end reset_proxy()

	private function check_certificate_status( $domain ) {

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( array( 'hostname' => $domain ) ),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/cert', 'wp' );
		$response = wp_remote_request( $url, $data );

		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			$code = $error->get_error_code();

			if ( 'forward-not-found' === $code || 'certificate-not-found' === $code ) {
				return new WP_REST_Response( array( 'status' => 'missing-forward' ), 200 );
			}//end if

			return $error;
		}//end if

		$certificate_status = json_decode( $response['body'], true );
		if ( ! isset( $certificate_status['status'] ) ) {
			return new WP_Error(
				'certificate-status-not-found',
				_x( 'Status of certificate not found.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		switch ( $certificate_status['status'] ) {
			case 'FAILED':
				return new WP_Error(
					'certificate-status-failed',
					_x( 'Status of certificate failed. Contact Nelio Team to fix this.', 'text', 'nelio-ab-testing' )
				);

			case 'PENDING_VALIDATION':
				return new WP_REST_Response(
					array(
						'status'      => 'cert-validation-pending',
						'recordName'  => $certificate_status['record']['Name'],
						'recordValue' => $certificate_status['record']['Value'],
					),
					200
				);

			case 'SUCCESS':
				return new WP_REST_Response(
					array(
						'status' => 'cert-validation-success',
					),
					200
				);
		}//end switch

		return new WP_Error(
			'certificate-status-failed',
			_x( 'Status of certificate failed. Contact Nelio Team to fix this.', 'text', 'nelio-ab-testing' )
		);
	}//end check_certificate_status()

	private function create_domain_forwarding() {

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/domain', 'wp' );
		$response = wp_remote_request( $url, $data );

		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			$code = $error->get_error_code();

			if ( 'forward-not-found' === $code ) {
				return new WP_REST_Response( array( 'status' => 'missing-forward' ), 200 );
			}//end if

			return $error;
		}//end if

		return new WP_REST_Response( array( 'status' => 'success' ), 200 );
	}//end create_domain_forwarding()
}//end class
