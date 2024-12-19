<?php
/**
 * This file contains the class that defines generic REST API endpoints.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Generic_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_REST_API
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Generic_REST_Controller the single instance of this class.
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

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		$proxy_route = $this->get_proxy_route();
		if ( $proxy_route ) {
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
		}//end if

		register_rest_route(
			nelioab()->rest_namespace,
			'/plugins/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_plugins' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/plugin/clean',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'clean_plugin' ),
					'permission_callback' => array( $this, 'check_if_user_can_deactivate_plugin' ),
				),
			)
		);
	}//end register_routes()

	/**
	 * Proxies GET requests to Nelio’s cloud.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function proxy( $request ) {
		$path   = $request->get_param( 'path' );
		$params = $request->get_params();
		unset( $params['path'] );
		$url      = add_query_arg( $params, nab_get_api_url( '', 'wp' ) . $path );
		$response = wp_remote_get( $url ); // phpcs:ignore
		if ( ! nab_is_response_valid( $response ) ) {
			$code    = nab_array_get( $response, array( 'response', 'code' ), 500 );
			$message = nab_array_get( $response, array( 'response', 'message' ), 'Unknown error' );
			$message = nab_array_get( $response, array( 'body' ), $message );
			$json    = is_string( $message ) ? json_decode( $message, ARRAY_A ) : false;
			return empty( $json )
				? new WP_REST_Response( $message, $code )
				: new WP_REST_Response( $json, $code );
		}//end if

		$body = nab_array_get( $response, array( 'body' ), '' );
		$body = is_string( $body ) ? json_decode( $body, ARRAY_A ) : false;
		return empty( $body )
			? new WP_REST_Response()
			: new WP_REST_Response( $body );
	}//end proxy()

	/**
	 * Returns all active plugins.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_plugins() {
		$plugins = array_keys( get_plugins() );
		$actives = array_map( 'is_plugin_active', $plugins );
		$plugins = array_combine( $plugins, $actives );
		$plugins = array_keys( array_filter( $plugins ) );

		return new WP_REST_Response( $plugins, 200 );
	}//end get_plugins()

	/**
	 * Returns whether the user can use the plugin or not.
	 *
	 * @return boolean whether the user can use the plugin or not.
	 */
	public function check_if_user_can_deactivate_plugin() {
		return current_user_can( 'deactivate_plugin', nelioab()->plugin_file );
	}//end check_if_user_can_deactivate_plugin()

	/**
	 * Cleans the plugin. If a reason is provided, it tells our cloud what happened.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function clean_plugin( $request ) {

		$nonce = $request['nabnonce'];
		if ( ! wp_verify_nonce( $nonce, 'nab_clean_plugin_data_' . get_current_user_id() ) ) {
			return new WP_Error( 'invalid-nonce' );
		}//end if

		$reason = $request['reason'];
		$reason = ! empty( $reason ) ? $reason : 'none';

		// 1. Clean cloud.
		$data = array(
			'method'    => 'DELETE',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'body'      => wp_json_encode( array( 'reason' => $reason ) ),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );
		$error    = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Clean database.
		$experiment_ids = nab_get_all_experiment_ids();
		foreach ( $experiment_ids as $id ) {
			wp_delete_post( $id, true );
		}//end foreach
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", array( 'nab_%' ) ) ); // phpcs:ignore

		return new WP_REST_Response( true, 200 );
	}//end clean_plugin()

	private function get_proxy_route() {
		$settings      = Nelio_AB_Testing_Settings::instance();
		$proxy_setting = $settings->get( 'cloud_proxy_setting' );
		if ( 'rest' !== nab_array_get( $proxy_setting, 'mode', false ) ) {
			return false;
		}//end if

		$value = nab_array_get( $proxy_setting, 'value', '' );
		$value = is_string( $value ) ? $value : '';
		if ( ! preg_match( '/^\/[a-z0-9-]+\/[a-z0-9-]+$/', $value ) ) {
			return false;
		}//end if

		$parts     = is_string( $value ) ? explode( '/', $value ) : array();
		$namespace = nab_array_get( $parts, 1 );
		$route     = nab_array_get( $parts, 2 );

		if ( empty( $namespace ) || empty( $route ) ) {
			return false;
		}//end if

		return array(
			'namespace' => $namespace,
			'route'     => "/{$route}",
		);
	}//end get_proxy_route()
}//end class
