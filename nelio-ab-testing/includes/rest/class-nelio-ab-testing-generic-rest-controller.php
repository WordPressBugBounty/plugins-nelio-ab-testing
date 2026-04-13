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
	 * Hooks into WordPress.
	 *
	 * @return void
	 * @since  5.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this, 'maybe_register_proxy_route' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			nelioab()->rest_namespace,
			'/plugins',
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
					'args'                => array(
						'nabnonce'              => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => fn( $v ) => is_string( $v ) && wp_verify_nonce( $v, 'nab_clean_plugin_data_' . get_current_user_id() ),
						),
						'deleteStagingDataOnly' => array(
							'required'          => false,
							'type'              => 'boolean',
							'sanitize_callback' => fn( $v ) => ! empty( $v ),
						),
						'reason'                => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => fn( $v ) => trim( sanitize_text_field( $v ) ),
						),
					),
				),
			)
		);
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
	 * Returns all active plugins.
	 *
	 * @return list<string>
	 */
	public function get_plugins() {
		$plugins = array_keys( get_plugins() );
		$actives = array_map( 'is_plugin_active', $plugins );
		$plugins = array_combine( $plugins, $actives );
		$plugins = array_keys( array_filter( $plugins ) );
		return $plugins;
	}

	/**
	 * Returns whether the user can use the plugin or not.
	 *
	 * @return boolean whether the user can use the plugin or not.
	 */
	public function check_if_user_can_deactivate_plugin() {
		return current_user_can( 'deactivate_plugin', nelioab()->plugin_file );
	}

	/**
	 * Cleans the plugin. If a reason is provided, it tells our cloud what happened.
	 *
	 * @param WP_REST_Request<array{deleteStagingDataOnly?:boolean,reason?:string}> $request Full data about the request.
	 *
	 * @return true|WP_Error
	 */
	public function clean_plugin( $request ) {
		$delete_staging_data_only = ! empty( $request['deleteStagingDataOnly'] ) && nab_is_staging();

		$reason = $request['reason'] ?? 'none';
		$reason = ! empty( $reason ) ? $reason : 'none';

		// 1. Maybe clean cloud.
		if ( ! $delete_staging_data_only ) {
			$params = array( 'reason' => $reason );
			$body   = wp_json_encode( $params );
			assert( ! empty( $body ) );

			$data = array(
				'method'    => 'DELETE',
				'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
				'sslverify' => ! nab_does_api_use_proxy(),
				'headers'   => array(
					'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
					'accept'        => 'application/json',
					'content-type'  => 'application/json',
				),
				'body'      => $body,
			);

			$url      = nab_get_api_url( '/site/' . nab_get_site_id(), 'wp' );
			$response = wp_remote_request( $url, $data );
			$response = nab_extract_response_body( $response );
			if ( is_wp_error( $response ) ) {
				return $response; // @codeCoverageIgnore
			}
		}

		// 2. Clean database.
		$manager        = nelioab()->manager();
		$experiment_ids = $manager->get_all_experiment_ids();
		foreach ( $experiment_ids as $id ) {
			wp_delete_post( $id, true );
		}

		/** @var wpdb */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE option_name LIKE %s',
				$wpdb->options,
				'nab_%'
			) ?? ''
		);

		return true;
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
