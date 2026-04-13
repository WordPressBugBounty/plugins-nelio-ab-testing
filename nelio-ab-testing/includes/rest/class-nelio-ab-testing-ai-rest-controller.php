<?php

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

class Nelio_AB_Testing_AI_REST_Controller extends WP_REST_Controller {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_ga4_routes' ) );
		add_action( 'rest_api_init', array( $this, 'maybe_register_ai_routes' ) );
	}

	/**
	 * Registers GA4-related routes.
	 *
	 * @return void
	 */
	public function register_ga4_routes() {
		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/ga4-connect',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connect_ga4' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/ga4-properties',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ga4_properties' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function maybe_register_ai_routes() {
		if ( ! nab_is_ai_active() ) {
			return; // @codeCoverageIgnore
		}

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/test-candidates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'maybe_get_test_candidates' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'get_test_candidates' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'analysis' => array(
							'required'    => true,
							'description' => 'Information about the site.',
							'type'        => 'AiSiteAnalysis',
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/test-hypotheses',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'get_test_hypotheses' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'language' => array(
							'required'    => true,
							'description' => 'Site language',
							'type'        => 'string',
						),
						'id'       => array(
							'required'    => true,
							'description' => 'Post ID',
							'type'        => 'number',
						),
						'type'     => array(
							'required'    => true,
							'description' => 'Post type',
							'type'        => 'string',
						),
						'content'  => array(
							'required'    => true,
							'description' => 'Post content summary',
							'type'        => 'AiContentPiece[]',
						),
						'url'      => array(
							'required'    => true,
							'description' => 'Post URL',
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/latest-experiments',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_latest_experiments' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/top-viewed-items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_top_viewed_items' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/settings',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_ai_settings' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_options' ),
					'args'                => array(
						'settings' => array(
							'required'          => true,
							'description'       => 'Nelio AI privacy settings',
							'sanitize_callback' => array( $this, 'sanitize_ai_settings' ),
							'type'              => 'AiSettings',
						),
					),
				),
			)
		);
	}

	/**
	 * Returns cached test candidates from AWS.
	 *
	 * @return mixed|WP_Error
	 */
	public function maybe_get_test_candidates() {
		$site_id = nab_get_site_id();

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_ai_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url = nab_get_api_url( "/ai/{$site_id}/test-candidates", 'wp' );
		$url = add_query_arg( 'hash', $this->get_ai_settings_hash(), $url );

		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error-011', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		/** @var list<array<string,mixed>>|null $result */
		if ( ! is_array( $result ) || empty( $result['0']['rationale'] ) ) {
			return new WP_Error( 'nelio-ai-error-013', 'Unable to retrieve test candidates' );
		}

		return $result;
	}

	/**
	 * Returns new test candidates from AWS.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return mixed|WP_Error
	 */
	public function get_test_candidates( $request ) {
		$site_id  = nab_get_site_id();
		$analysis = $request['analysis'];

		$body = wp_json_encode( $analysis );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_ai_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url = nab_get_api_url( "/ai/{$site_id}/test-candidates", 'wp' );
		$url = add_query_arg( 'hash', $this->get_ai_settings_hash(), $url );

		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error-021', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		/** @var list<array<string,mixed>>|null $result */
		if ( ! is_array( $result ) || empty( $result['0']['rationale'] ) ) {
			return new WP_Error( 'nelio-ai-error-024', 'Unable to retrieve test candidates' );
		}

		return $result;
	}

	/**
	 * Retrieves test hypotheses from AWS.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return mixed|WP_Error
	 */
	public function get_test_hypotheses( $request ) {
		$site_id      = nab_get_site_id();
		$language     = $request['language'];
		$post_id      = absint( $request['id'] );
		$post_type    = $request['type'];
		$post_content = $request['content'];
		$post_url     = $request['url'];

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return new WP_Error( 'post-not-found' );
		}

		if ( get_post_type( $post ) !== $post_type ) {
			return new WP_Error( 'invalid-post-type' );
		}

		$candidate = array(
			'type'       => 'post',
			'attributes' => array(
				'language' => $language,
				'id'       => $post_id,
				'type'     => $post_type,
				'content'  => $post_content,
				'url'      => $post_url,
			),
		);

		$params = array( 'candidate' => $candidate );
		$body   = wp_json_encode( $params );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_ai_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url = nab_get_api_url( "/ai/{$site_id}/test-hypotheses", 'wp' );
		$url = add_query_arg( 'hash', $this->get_ai_settings_hash(), $url );

		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error-031', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		/** @var list<array<string,mixed>>|null $result */
		if ( ! is_array( $result ) || empty( $result['0']['name'] ) ) {
			return new WP_Error( 'nelio-ai-error-033', 'Unable to retrieve hypotheses' );
		}

		return $result;
	}

	/**
	 * Returns latest experiments.
	 *
	 * @return list<array<string,mixed>>
	 */
	public function get_latest_experiments() {
		$max_number_of_experiments = 50;

		$running = array_merge(
			nab_get_running_experiments(),
			nab_get_running_heatmaps()
		);

		$missing = $max_number_of_experiments - count( $running );
		$missing = $missing < 0 ? 0 : $missing;
		$others  = array();
		if ( $missing ) {
			/** @var wpdb */
			global $wpdb;
			/** @var list<int> */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$others = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM %i p
					WHERE p.post_type = \"nab_experiment\" AND
								p.post_status <> 'trash'
					ORDER BY p.post_modified DESC
					LIMIT %d
					",
					$wpdb->posts,
					$missing
				)
			);
		}
		$others = array_map( 'nab_get_experiment', $others );
		$others = array_filter( $others, fn( $e ) => ! is_wp_error( $e ) );
		$others = array_values( $others );

		$result = array_merge( $running, $others );
		$result = array_map( fn( $e ) => $e->json(), $result );
		return $result;
	}

	/**
	 * Retursn top viewed items.
	 *
	 * @return (
	 *    list< array{type:'url',url:string,monthlyViews:int} | array{type:'post',postId:int,postType:string,monthlyViews:int} > |
	 *    WP_Error
	 * )
	 */
	public function get_top_viewed_items() {
		$data = $this->get_google_analytics_data();
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return array_map( fn( $row ) => $this->process_ga_row( $row ), $data );
	}

	/**
	 * Redirect page on GA4 connect.
	 *
	 * @return void
	 */
	public function connect_ga4() {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' ); // @codeCoverageIgnore
		}
		echo '<!DOCTYPE html>';
		echo "\n";
		echo '<html><head><script>window.close();</script></head></html>';
		nab_die();
	}

	/**
	 * Gets GA4 properties.
	 *
	 * @return mixed|WP_Error
	 */
	public function get_ga4_properties() {
		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = add_query_arg(
			'siteId',
			nab_get_site_id(),
			nab_get_api_url( '/ga4/properties', 'wp' )
		);
		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error-911', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		return $result;
	}

	/**
	 * Updates AI settings.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return array{type:'ready',privacy:mixed,analytics:mixed}
	 */
	public function update_ai_settings( $request ) {
		/** @var array<string,mixed> */
		$ai_settings = $request['settings'];

		$settings = Nelio_AB_Testing_Settings::instance();
		/** @var array<string,mixed> */
		$options = get_option( $settings->get_name(), array() );

		$options['ai_privacy_settings']   = $ai_settings['privacy'];
		$options['google_analytics_data'] = $ai_settings['analytics'];

		update_option( $settings->get_name(), $options );
		update_option( 'nab_show_ai_setup_screen', 'no' );

		$result = array(
			'type'      => 'ready',
			'privacy'   => $options['ai_privacy_settings'],
			'analytics' => $options['google_analytics_data'],
		);
		return $result;
	}

	/**
	 * Sanitizes AI Settings.
	 *
	 * @param array<string,array<string,mixed>> $input Input.
	 *
	 * @return array<string,mixed>
	 */
	public function sanitize_ai_settings( $input ) {
		$settings  = Nelio_AB_Testing_Settings::instance();
		$analytics = $settings->get( 'google_analytics_data' );
		$privacy   = $settings->get( 'ai_privacy_settings' );

		$schema = Z::object(
			array(
				'analytics' => Z::object(
					array(
						'enabled'      => Z::boolean()->default( $analytics['enabled'] ),
						'propertyId'   => Z::string()->default( $analytics['propertyId'] ),
						'propertyName' => Z::string()->default( $analytics['propertyName'] ),
					)
				)->default( $analytics ),
				'privacy'   => Z::object(
					array(
						'postTypes'                   => Z::array( Z::string() )->default( $privacy['postTypes'] ),
						'isWooCommerceEnabled'        => Z::boolean()->default( $privacy['isWooCommerceEnabled'] ),
						'includeWooCommerceOrderInfo' => Z::boolean()->default( $privacy['includeWooCommerceOrderInfo'] ),
					)
				)->default( $privacy ),
			)
		)->catch(
			array(
				'analytics' => $analytics,
				'privacy'   => $privacy,
			),
		);

		$result = $schema->safe_parse( $input );
		assert( $result['success'] );
		/** @var array<string,mixed> */
		return $result['data'];
	}

	/**
	 * Returns Google Analytics data.
	 *
	 * @return list<array<string,mixed>>|WP_Error
	 */
	private function get_google_analytics_data() {
		$settings = Nelio_AB_Testing_Settings::instance();
		$ga_data  = $settings->get( 'google_analytics_data' );
		$property = $ga_data['propertyId'];
		if ( empty( $property ) ) {
			return array();
		}

		$params = array( 'propertyId' => $property );
		$body   = wp_json_encode( $params );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url = add_query_arg(
			'siteId',
			nab_get_site_id(),
			nab_get_api_url( '/ga4/report', 'wp' )
		);

		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error-913', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		$result = is_array( $result ) ? $result : array();
		/** @var list<array<string,mixed>> */
		return $result;
	}

	/**
	 * Converts GA Row.
	 *
	 * @param array<string,mixed> $row Data row.
	 *
	 * @return (
	 *    array{type:'url',url:string,monthlyViews:int} |
	 *    array{type:'post',postId:int,postType:string,monthlyViews:int}
	 * )
	 */
	private function process_ga_row( $row ) {
		$path  = $row['path'] ?? '';
		$path  = is_string( $path ) ? $path : '';
		$views = absint( $row['views'] ?? 0 );

		/**
		 * Filters the URL used in GA4 reports.
		 *
		 * @param string $url  The URL generated by combining `home_url` and `path`.
		 * @param string $path The path as reported by GA4.
		 *
		 * @since 8.0.0
		 */
		$url     = apply_filters( 'nab_ai_item_url', home_url( $path ), $path );
		$post_id = nab_url_to_postid( $url );
		if ( ! $post_id ) {
			return array(
				'type'         => 'url',
				'url'          => $url,
				'monthlyViews' => $views,
			);
		}

		$post_type = get_post_type( $post_id );
		$post_type = is_string( $post_type ) ? $post_type : '';
		return array(
			'type'         => 'post',
			'postId'       => $post_id,
			'postType'     => $post_type,
			'monthlyViews' => $views,
		);
	}

	/**
	 * Returns AI Settings hash.
	 *
	 * @return string
	 */
	private function get_ai_settings_hash() {
		$settings    = Nelio_AB_Testing_Settings::instance();
		$ai_settings = array(
			'privacy'   => $settings->get( 'ai_privacy_settings' ),
			'analytics' => $settings->get( 'google_analytics_data' ),
		);
		$ai_settings = wp_json_encode( $ai_settings );
		$ai_settings = is_string( $ai_settings ) ? $ai_settings : '';
		return md5( $ai_settings );
	}
}
