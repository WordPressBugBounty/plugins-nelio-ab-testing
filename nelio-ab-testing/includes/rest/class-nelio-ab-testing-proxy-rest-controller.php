<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing cloud forwarding settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      8.4.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Proxy_REST_Controller extends WP_REST_Controller {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 * @since  8.4.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'maybe_register_proxy_route' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers proxy route.
	 *
	 * @return void
	 */
	public function maybe_register_proxy_route() {
		$proxy_route = $this->get_proxy_route();
		if ( empty( $proxy_route ) ) {
			return;
		}

		register_rest_route(
			$proxy_route['namespace'],
			$proxy_route['route'],
			array(
				array(
					'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
					'callback'            => array( $this, 'proxy' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'path' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Proxies GET requests to Nelio’s cloud.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return mixed|WP_Error
	 */
	public function proxy( $request ) {
		$path   = $request->get_param( 'path' );
		$path   = is_string( $path ) ? $path : '';
		$params = $request->get_params();
		unset( $params['path'] );
		$url = add_query_arg( $params, nab_get_api_url( '', 'wp' ) . $path );

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response = wp_remote_get( $url );
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response; // @codeCoverageIgnore
		}

		return $response;
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			nelioab()->rest_namespace,
			'/domain/check',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'check_domain' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_options' ),
					'args'                => array(
						'domain'       => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'domainStatus' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
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
					'permission_callback' => nab_capability_checker( 'manage_nab_options' ),
				),
			)
		);
	}

	/**
	 * Checks and manages the domain forwarding status.
	 *
	 * @param WP_REST_Request<array{domain:string,domainStatus:string}> $request Full data about the request.
	 *
	 * @return (
	 *    array{status:'cert-validation-pending',recordName:string,recordValue:string} |
	 *    array{status:'cert-validation-success'} |
	 *    array{status:'missing-forward'} |
	 *    array{status:'success'} |
	 *    WP_Error
	 * )
	 */
	public function check_domain( $request ) {

		$domain        = $request['domain'] ?? '';
		$domain_status = $request['domainStatus'] ?? '';

		switch ( $domain_status ) {

			case 'disabled':
			case 'missing-forward':
			case 'cert-validation-pending':
				return $this->check_certificate_status( $domain );

			case 'cert-validation-success':
				return $this->create_domain_forwarding();

			case 'success':
				return array( 'status' => 'success' );

			default:
				return new WP_Error(
					'bad-request',
					_x( 'Domain status value is not valid.', 'text', 'nelio-ab-testing' )
				);
		}
	}

	/**
	 * Resets the domain forwarding settings.
	 *
	 * @return 'OK'|WP_Error
	 */
	public function reset_proxy() {

		$data = array(
			'method'    => 'DELETE',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
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
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response; // @codeCoverageIgnore
		}

		return 'OK';
	}

	/**
	 * Checks the certificate status for the given domain.
	 *
	 * @param string $domain Domain.
	 *
	 * @return (
	 *    array{status:'cert-validation-pending',recordName:string,recordValue:string} |
	 *    array{status:'cert-validation-success'} |
	 *    array{status:'missing-forward'} |
	 *    WP_Error
	 * )
	 */
	private function check_certificate_status( $domain ) {
		$params = array( 'hostname' => $domain );
		$body   = wp_json_encode( $params );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/cert', 'wp' );
		$response = wp_remote_request( $url, $data );

		$certificate_status = nab_extract_response_body( $response );
		if ( is_wp_error( $certificate_status ) ) {
			$code = $certificate_status->get_error_code();

			if ( 'forward-not-found' === $code || 'certificate-not-found' === $code ) {
				return array( 'status' => 'missing-forward' );
			}

			return $certificate_status;
		}

		/** @var array{status?:string, record: array{Name:string, Value:string}} $certificate_status */
		if ( ! isset( $certificate_status['status'] ) ) {
			return new WP_Error(
				'certificate-status-not-found',
				_x( 'Status of certificate not found.', 'text', 'nelio-ab-testing' )
			);
		}

		switch ( $certificate_status['status'] ) {
			case 'FAILED':
				return new WP_Error(
					'certificate-status-failed',
					_x( 'Status of certificate failed. Contact Nelio Team to fix this.', 'text', 'nelio-ab-testing' )
				);

			case 'PENDING_VALIDATION':
				return array(
					'status'      => 'cert-validation-pending',
					'recordName'  => $certificate_status['record']['Name'],
					'recordValue' => $certificate_status['record']['Value'],
				);

			case 'SUCCESS':
				return array( 'status' => 'cert-validation-success' );
		}

		return new WP_Error(
			'certificate-status-failed',
			_x( 'Status of certificate failed. Contact Nelio Team to fix this.', 'text', 'nelio-ab-testing' )
		);
	}

	/**
	 * Creates a domain forwarding.
	 *
	 * @return (
	 *    array{status:'success'} |
	 *    array{status:'missing-forward'} |
	 *    WP_Error
	 * )
	 */
	private function create_domain_forwarding() {

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/domain', 'wp' );
		$response = wp_remote_request( $url, $data );

		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();

			if ( 'forward-not-found' === $code ) {
				return array( 'status' => 'missing-forward' );
			}

			return $response;
		}

		return array( 'status' => 'success' );
	}

	/**
	 * Returns proxy route.
	 *
	 * @return false|array{namespace:string, route:string}
	 */
	private function get_proxy_route() {
		$settings      = Nelio_AB_Testing_Settings::instance();
		$proxy_setting = $settings->get( 'cloud_proxy_setting' );

		$mode = $proxy_setting['mode'];
		if ( 'rest' !== $mode ) {
			return false;
		}

		$value = $proxy_setting['value'];
		if ( ! preg_match( '/^\/[a-z0-9-]+\/[a-z0-9-]+$/', $value ) ) {
			return false;
		}

		$parts     = explode( '/', $value );
		$namespace = $parts[1] ?? '';
		$route     = $parts[2] ?? '';

		assert( ! empty( $namespace ) );
		assert( ! empty( $route ) );

		return array(
			'namespace' => $namespace,
			'route'     => "/{$route}",
		);
	}
}
