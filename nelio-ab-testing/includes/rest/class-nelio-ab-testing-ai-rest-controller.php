<?php

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_AI_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  8.0.0
	 * @var    Nelio_AB_Testing_AI_REST_Controller|null
	 */
	protected static $instance;


	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_AI_REST_Controller the single instance of this class.
	 *
	 * @since  8.0.0
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Hooks into WordPress.
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		if ( ! nab_is_ai_active() ) {
			return;
		}//end if

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/test-candidates',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'maybe_get_test_candidates' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
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
					'args'                => array(),
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
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/ga4-connect',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connect_ga4' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
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
					'args'                => array(),
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
	}//end register_routes()

	public function maybe_get_test_candidates() {
		$site_id = nab_get_site_id();

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_ai_request_timeout', 60 ),
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
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'nelio-ai-error-011', $response->get_error_code() . ' ' . $response->get_error_message() );
		}//end if

		$result = json_decode( $response['body'], true );
		if ( isset( $result['errorType'] ) && isset( $result['errorMessage'] ) ) {
			return new WP_Error( 'nelio-ai-error-012', $result['errorType'] . ': ' . $result['errorMessage'] );
		}//end if

		if ( ! is_array( $result ) || empty( nab_array_get( $result, '0.rationale' ) ) ) {
			return new WP_Error( 'nelio-ai-error-013', 'Unable to retrieve test candidates' );
		}//end if

		return new WP_REST_Response( $result, 200 );
	}//end maybe_get_test_candidates()

	public function get_test_candidates( $request ) {
		$site_id  = nab_get_site_id();
		$analysis = $request['analysis'];

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_ai_request_timeout', 60 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( $analysis ),
		);

		$url = nab_get_api_url( "/ai/{$site_id}/test-candidates", 'wp' );
		$url = add_query_arg( 'hash', $this->get_ai_settings_hash(), $url );

		$response = wp_remote_request( $url, $data );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'nelio-ai-error-021', $response->get_error_code() . ' ' . $response->get_error_message() );
		}//end if

		$result = json_decode( $response['body'], true );
		if ( isset( $result['errorType'] ) && isset( $result['errorMessage'] ) ) {
			return new WP_Error( 'nelio-ai-error-022', $result['errorType'] . ': ' . $result['errorMessage'] );
		}//end if

		if ( 'Endpoint request timed out' === nab_array_get( $result, 'message' ) ) {
			return new WP_Error( 'nelio-ai-error-023', 'Nelio AI request timed out' );
		}//end if

		if ( ! is_array( $result ) || empty( nab_array_get( $result, '0.rationale' ) ) ) {
			return new WP_Error( 'nelio-ai-error-024', 'Unable to retrieve test candidates' );
		}//end if

		return new WP_REST_Response( $result, 200 );
	}//end get_test_candidates()

	public function get_test_hypotheses( $request ) {
		$site_id      = nab_get_site_id();
		$language     = $request['language'];
		$post_id      = $request['id'];
		$post_type    = $request['type'];
		$post_content = $request['content'];
		$post_url     = $request['url'];

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return new WP_Error( 'post-not-found' );
		}//end if

		if ( get_post_type( $post ) !== $post_type ) {
			return new WP_Error( 'invalid-post-type' );
		}//end if

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

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_ai_request_timeout', 60 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( array( 'candidate' => $candidate ) ),
		);

		$url = nab_get_api_url( "/ai/{$site_id}/test-hypotheses", 'wp' );
		$url = add_query_arg( 'hash', $this->get_ai_settings_hash(), $url );

		$response = wp_remote_request( $url, $data );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'nelio-ai-error-031', $response->get_error_code() . ' ' . $response->get_error_message() );
		}//end if

		$result = json_decode( $response['body'], true );
		if ( isset( $result['errorType'] ) && isset( $result['errorMessage'] ) ) {
			return new WP_Error( 'nelio-ai-error-032', $result['errorType'] . ': ' . $result['errorMessage'] );
		}//end if

		if ( ! is_array( $result ) || empty( nab_array_get( $result, '0.name' ) ) ) {
			return new WP_Error( 'nelio-ai-error-033', 'Unable to retrieve hypotheses' );
		}//end if

		return new WP_REST_Response( $result, 200 );
	}//end get_test_hypotheses()

	public function get_latest_experiments() {
		$max_number_of_experiments = 50;

		$helper  = Nelio_AB_Testing_Experiment_REST_Controller::instance();
		$running = array_merge(
			nab_get_running_experiments(),
			nab_get_running_heatmaps()
		);

		$missing = $max_number_of_experiments - count( $running );
		$missing = $missing < 0 ? 0 : $missing;
		$others  = array();
		if ( $missing ) {
			global $wpdb;
			$others = $wpdb->get_col( // phpcs:ignore
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts p
					WHERE p.post_type = \"nab_experiment\" AND
								p.post_status <> 'trash'
					ORDER BY p.post_modified DESC
					LIMIT %d
					",
					$missing
				)
			);
		}//end if
		$others = array_map( 'nab_get_experiment', $others );
		$others = array_filter( $others, fn( $e ) => ! is_wp_error( $e ) );
		$others = array_values( $others );

		$result = array_merge( $running, $others );
		$result = array_map( array( $helper, 'json' ), $result );

		return new WP_REST_Response( $result, 200 );
	}//end get_latest_experiments()

	public function get_top_viewed_items() {
		$data = $this->get_google_analytics_data();
		if ( is_wp_error( $data ) ) {
			return $data;
		}//end if

		$data   = is_array( $data ) ? $data : array();
		$result = array_map(
			function ( $row ) {
				$path  = $row['path'];
				$views = $row['views'];

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
				}//end if

				return array(
					'type'         => 'post',
					'postId'       => $post_id,
					'postType'     => get_post_type( $post_id ),
					'monthlyViews' => $views,
				);
			},
			$data
		);

		return new WP_REST_Response( $result, 200 );
	}//end get_top_viewed_items()

	public function connect_ga4() {
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo '<!DOCTYPE html>';
		echo '<html><head><script>window.close();</script></head></html>';
		die();
	}//end connect_ga4()

	public function get_ga4_properties() {
		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 60 ),
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
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'nelio-ai-error-911', $response->get_error_code() . ' ' . $response->get_error_message() );
		}//end if

		$result = json_decode( $response['body'], true );
		if ( isset( $result['errorType'] ) && isset( $result['errorMessage'] ) ) {
			return new WP_Error( 'nelio-ai-error-912', $result['errorType'] . ': ' . $result['errorMessage'] );
		}//end if

		return new WP_REST_Response( $result, 200 );
	}//end get_ga4_properties()

	public function update_ai_settings( $request ) {
		$ai_settings = $request['settings'];

		$settings = Nelio_AB_Testing_Settings::instance();
		$options  = get_option( $settings->get_name(), array() );

		$options['ai_privacy_settings']   = $ai_settings['privacy'];
		$options['google_analytics_data'] = $ai_settings['analytics'];

		update_option( $settings->get_name(), $options );
		update_option( 'nab_show_ai_setup_screen', 'no' );

		$result = array(
			'type'      => 'ready',
			'privacy'   => $options['ai_privacy_settings'],
			'analytics' => $options['google_analytics_data'],
		);
		return new WP_REST_Response( $result, 200 );
	}//end update_ai_settings()

	public function sanitize_ai_settings( $input ) {
		$settings = Nelio_AB_Testing_Settings::instance();
		return array(
			'analytics' => wp_parse_args(
				isset( $input['analytics'] ) ? $input['analytics'] : $settings->get( 'google_analytics_data' ),
				$settings->get( 'google_analytics_data' )
			),
			'privacy'   => wp_parse_args(
				isset( $input['privacy'] ) ? $input['privacy'] : $settings->get( 'ai_privacy_settings' ),
				$settings->get( 'ai_privacy_settings' )
			),
		);
	}//end sanitize_ai_settings()

	private function get_google_analytics_data() {
		$settings = Nelio_AB_Testing_Settings::instance();
		$property = nab_array_get( $settings->get( 'google_analytics_data' ), 'propertyId', '' );
		if ( empty( $property ) ) {
			return array();
		}//end if

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 60 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( array( 'propertyId' => $property ) ),
		);

		$url      = add_query_arg(
			'siteId',
			nab_get_site_id(),
			nab_get_api_url( '/ga4/report', 'wp' )
		);
		$response = wp_remote_request( $url, $data );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'nelio-ai-error-913', $response->get_error_code() . ' ' . $response->get_error_message() );
		}//end if

		$result = json_decode( $response['body'], true );
		if ( isset( $result['errorType'] ) && isset( $result['errorMessage'] ) ) {
			return new WP_Error( 'nelio-ai-error-914', $result['errorType'] . ': ' . $result['errorMessage'] );
		}//end if

		return $result;
	}//end get_google_analytics_data()

	private function get_ai_settings_hash() {
		$settings    = Nelio_AB_Testing_Settings::instance();
		$ai_settings = array(
			'privacy'   => $settings->get( 'ai_privacy_settings' ),
			'analytics' => $settings->get( 'google_analytics_data' ),
		);
		return md5( wp_json_encode( $ai_settings ) );
	}//end get_ai_settings_hash()
}//end class
