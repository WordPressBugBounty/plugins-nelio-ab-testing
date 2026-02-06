<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing a Nelio A/B Testing account.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Account_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Account_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Account_REST_Controller the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/quota',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_quota' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/excluded-ips',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_excluded_ips' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_options' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/account',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_account_data' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/account/agency',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'get_agency_details' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(
						'license' => array(
							'description'       => _x( 'License Key', 'text', 'nelio-ab-testing' ),
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
			'/site/free',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'create_free_site' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/subscription',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'use_license_in_site' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/(?P<id>[\w\-]+)/subscription',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_license_from_site' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/(?P<id>[\w\-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_quota_limit_of_site' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'upgrade_subscription' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_subscription' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/uncancel',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'uncancel_subscription' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/quota',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'buy_more_quota' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sites_using_subscription' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/invoices',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_invoices_from_subscription' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/fastspring',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_fastspring_props' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Retrieves this site’s quota.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_site_quota() {
		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}

		$subs_quota = absint( $site['subscription']['quota'] ?? 0 );
		$subs_extra = absint( $site['subscription']['quotaExtra'] ?? 0 );
		$subs_month = absint( $site['subscription']['quotaPerMonth'] ?? 1 );

		$site_used  = absint( $site['usedMonthlyQuota'] ?? 0 );
		$site_month = absint( $site['maxMonthlyQuota'] ?? 0 );

		$sub_product = $site['subscription']['product'] ?? '';
		nab_update_subscription( nab_get_plan( $sub_product ) );

		$sub_addons = $site['subscription']['addons'] ?? array();
		nab_update_subscription_addons( $sub_addons );

		$available_quota = $site_month
			? max( 0, $site_month - $site_used )
			: max( 0, $subs_quota ) + max( 0, $subs_extra );

		$percentage = $site_month
			? floor( ( 100 * ( $available_quota + 0.1 ) ) / $site_month )
			: floor( ( 100 * ( $available_quota + 0.1 ) ) / $subs_month );

		$quota = array(
			'mode'           => $site_month ? 'site' : 'subscription',
			'availableQuota' => $available_quota,
			'percentage'     => min( $percentage, 100 ),
		);
		return new WP_REST_Response( $quota, 200 );
	}

	/**
	 * Retrieves this site’s quota.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_excluded_ips() {
		$site = $this->get_site( 'cloud' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}

		return new WP_REST_Response( $site['excludedIPs'] ?? array(), 200 );
	}

	/**
	 * Retrieves information about the site.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_account_data() {

		$site = $this->get_site( 'cloud' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}

		$account = $this->create_account_object( $site );
		nab_update_subscription( $account['plan'] );
		nab_update_subscription_addons( $account['addons'] );

		if ( 'OL-' === substr( $account['subscription'], 0, 3 ) ) {
			update_option( 'nab_is_subscription_deprecated', true );
		} else {
			delete_option( 'nab_is_subscription_deprecated' );
		}

		$account = $this->protect_agency_account( $account );
		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Retrieves information about the site.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data request.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_agency_details( $request ) {

		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}

		$account = $this->create_account_object( $site );
		$license = $request->get_param( 'license' );
		if ( $account['isAgency'] && $account['license'] !== $license ) {
			return new WP_Error(
				'invalid-license',
				_x( 'Invalid license code.', 'error', 'nelio-ab-testing' )
			);
		}

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Creates a new free site in AWS and updates the info in WordPress.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function create_free_site() {

		$experiments_page = admin_url( 'edit.php?post_type=nab_experiment' );

		if ( nab_get_site_id() ) {
			return new WP_REST_Response( $experiments_page, 200 );
		}

		$params = array(
			'id'         => nab_uuid(),
			'url'        => home_url(),
			'language'   => nab_get_language(),
			'timezone'   => nab_get_timezone(),
			'wpVersion'  => get_bloginfo( 'version' ),
			'nabVersion' => nelioab()->plugin_version,
		);

		$body = wp_json_encode( $params );
		if ( empty( $body ) ) {
			return new WP_Error( 'unable-to-create-request', _x( 'Something went wrong while preparing the request object.', 'text', 'nelio-ab-testing' ) );
		}

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => $body,
		);

		$url      = nab_get_api_url( '/site', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$body = nab_extract_response_body( $response );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		// Regenerate the account result and send it to the JS.
		$body = is_array( $body ) ? $body : array();
		if ( ! isset( $body['id'] ) || ! isset( $body['secret'] ) ) {
			return new WP_Error( 'invalid-result-type', _x( 'Invalid result type.', 'text', 'nelio-ab-testing' ) );
		}

		update_option( 'nab_site_id', $body['id'] );
		update_option( 'nab_api_secret', $body['secret'] );

		$this->notify_site_created();

		return new WP_REST_Response( $experiments_page, 200 );
	}

	/**
	 * Connects a site with a subscription.
	 *
	 * @param WP_REST_Request<array<license,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function use_license_in_site( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['license'] ) || ! is_string( $parameters['license'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'License key is missing.', 'text', 'nelio-ab-testing' )
			);
		}

		$license = trim( sanitize_text_field( $parameters['license'] ) );

		$body = wp_json_encode( array( 'license' => $license ) );
		if ( empty( $body ) ) {
			return new WP_Error( 'unable-to-create-request', _x( 'Something went wrong while preparing the request object.', 'text', 'nelio-ab-testing' ) );
		}

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

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$site_info = nab_extract_response_body( $response );
		if ( is_wp_error( $site_info ) ) {
			return $site_info;
		}

		/** @var TAWS_Site $site_info */
		$account = $this->create_account_object( $site_info );
		nab_update_subscription( $account['plan'] );
		nab_update_subscription_addons( $account['addons'] );

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Updates the quota limit of a site.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function update_quota_limit_of_site( $request ) {

		$parameters = $request->get_json_params();
		$site       = $request['id'];
		$params     = array(
			'maxMonthlyQuota' => $parameters['maxMonthlyQuota'],
		);

		$body = wp_json_encode( $params );
		if ( empty( $body ) ) {
			return new WP_Error( 'unable-to-create-request', _x( 'Something went wrong while preparing the request object.', 'text', 'nelio-ab-testing' ) );
		}

		$data = array(
			'method'    => 'PUT',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url      = nab_get_api_url( '/site/' . $site, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$site_info = nab_extract_response_body( $response );
		if ( is_wp_error( $site_info ) ) {
			return $site_info;
		}

		return new WP_REST_Response( $site_info, 200 );
	}

	/**
	 * Disconnects a site from a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function remove_license_from_site( $request ) {

		$site = $request['id'];

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

		$url      = nab_get_api_url( '/site/' . $site . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( nab_get_site_id() === $site ) {
			nab_update_subscription( 'free' );
			nab_update_subscription_addons( array() );
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Upgrades a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function upgrade_subscription( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['product'] ) || ! is_string( $parameters['product'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Plan is missing.', 'text', 'nelio-ab-testing' )
			);
		}

		$subscription = $request['id'];
		$product      = trim( sanitize_text_field( $parameters['product'] ) );
		$params       = array(
			'product'         => $product,
			'extraQuotaUnits' => absint( $parameters['extraQuotaUnits'] ?? 0 ),
		);

		$body = wp_json_encode( $params );
		if ( empty( $body ) ) {
			return new WP_Error( 'unable-to-create-request', _x( 'Something went wrong while preparing the request object.', 'text', 'nelio-ab-testing' ) );
		}
		$data = array(
			'method'    => 'PUT',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Cancels a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function cancel_subscription( $request ) {

		$subscription = $request['id'];

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

		$url      = nab_get_api_url( '/subscription/' . $subscription, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Un-cancels a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function uncancel_subscription( $request ) {

		$subscription = $request['id'];

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

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/uncancel', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Buys additional quota for a subscription.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function buy_more_quota( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['quantity'] ) || ! absint( $parameters['quantity'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Quantity is missing.', 'text', 'nelio-ab-testing' )
			);
		}

		if ( ! isset( $parameters['currency'] ) || ! is_string( $parameters['currency'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Currency is missing.', 'text', 'nelio-ab-testing' )
			);
		}

		$subscription = $request['id'];
		$quantity     = absint( $parameters['quantity'] );
		$quantity     = "$quantity";
		$currency     = trim( sanitize_text_field( $parameters['currency'] ) );

		$params = array(
			'subscriptionId' => $subscription,
			'quantity'       => $quantity,
			'currency'       => $currency,
		);

		$body = wp_json_encode( $params );
		if ( empty( $body ) ) {
			return new WP_Error( 'unable-to-create-request', _x( 'Something went wrong while preparing the request object.', 'text', 'nelio-ab-testing' ) );
		}

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

		$url      = nab_get_api_url( '/fastspring/quota', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->get_site( 'cloud' );
		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Obtains all sites connected with a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_sites_using_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/sites', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$sites = nab_extract_response_body( $response );
		if ( is_wp_error( $sites ) ) {
			return $sites;
		}

		// Move the current site to the top of the array of sites.
		/** @var list<TAWS_Site> $sites */
		$site_id = nab_get_site_id();

		/** @var list<string> $site_ids */
		$site_ids = array_column( $sites, 'id' );
		$index    = array_search( $site_id, $site_ids, true );
		if ( false === $index ) {
			return new WP_Error( 'missing-site', _x( 'Current site is missing from response.', 'text', 'nelio-ab-testing' ) );
		}

		$actual_site = $sites[ $index ];
		array_splice( $sites, $index, 1 );
		array_unshift( $sites, $actual_site );

		$sites = array_map(
			function ( $site ) {
				/** @var TAWS_Site $site */
				$aux = array(
					'id'               => $site['id'] ?? '',
					'url'              => $site['url'] ?? '',
					'isCurrentSite'    => nab_get_site_id() === ( $site['id'] ?? '' ),
					'maxMonthlyQuota'  => $site['maxMonthlyQuota'] ?? -1,
					'usedMonthlyQuota' => $site['usedMonthlyQuota'] ?? 0,
				);

				if ( $aux['isCurrentSite'] ) {
					$aux['actualUrl'] = home_url();
				}

				return $aux;
			},
			$sites
		);

		if ( ! current_user_can( 'manage_nab_account' ) ) {
			$sites = array_filter( $sites, fn( $s ) => $s['isCurrentSite'] );
		}

		return new WP_REST_Response( $sites, 200 );
	}

	/**
	 * Obtains the invoices of a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_invoices_from_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/invoices', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$invoices = nab_extract_response_body( $response );
		if ( is_wp_error( $invoices ) ) {
			return $invoices;
		}

		// Regenerate the invoices result and send it to the JS.
		$invoices = is_array( $invoices ) ? $invoices : array();
		$invoices = array_map(
			function ( $invoice ) {
				/** @var array{chargeDate:string} $invoice */
				$time = strtotime( $invoice['chargeDate'] );
				if ( false === $time ) {
					return $invoice;
				}
				/** @var string */
				$date_format           = get_option( 'date_format' );
				$invoice['chargeDate'] = gmdate( $date_format, $time );
				return $invoice;
			},
			$invoices
		);

		return new WP_REST_Response( $invoices, 200 );
	}

	/**
	 * Obtains fastspring related info (products, currency, etc)
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_fastspring_props() {
		$products = get_transient( 'nab_products' );
		if ( false === $products ) {
			$data = array(
				'method'    => 'GET',
				'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
				'sslverify' => ! nab_does_api_use_proxy(),
				'headers'   => array(
					'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
					'accept'        => 'application/json',
					'content-type'  => 'application/json',
				),
			);

			$url      = nab_get_api_url( '/fastspring/products/?addons=true', 'wp' );
			$response = wp_remote_request( $url, $data );

			// If the response is an error, leave.
			$products = nab_extract_response_body( $response );
			if ( is_wp_error( $products ) ) {
				return $products;
			}

			// Regenerate the products result and send it to the JS.
			$products = is_array( $products ) ? $products : array();
			$products = array_map(
				function ( $product ) {
					/** @var TAWS_Product $product */
					$from = isset( $product['upgradeableFrom'] ) ? $product['upgradeableFrom'] : '';
					if ( ! is_array( $from ) ) {
						$from = empty( $from ) ? array() : array( $from );
					}
					return array(
						'id'                => $product['product'] ?? '',
						'plan'              => ! empty( $product['isAddon'] ) ? 'addon' : nab_get_plan( $product['product'] ?? '' ),
						'period'            => nab_get_period( $product['product'] ?? '' ),
						'displayName'       => $product['display'] ?? '',
						'price'             => $product['pricing']['price'] ?? '',
						'quantityDiscounts' => $product['pricing']['quantityDiscounts'] ?? array(),
						'description'       => $product['description']['full'] ?? '',
						'attributes'        => $product['attributes'] ?? array(),
						'isAddon'           => ! empty( $product['isAddon'] ),
						'isSubscription'    => ! empty( $product['isSubscription'] ),
						'upgradeableFrom'   => $from,
						'allowedAddons'     => $product['allowedAddons'] ?? array(),
					);
				},
				$products
			);

			set_transient( 'nab_products', $products, HOUR_IN_SECONDS );
		}

		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}

		$is_agency_subs  = ! empty( $site['subscription']['isAgency'] );
		$is_regular_subs = 'regular' === ( $site['subscription']['mode'] ?? '' );
		$subs_id         = $site['subscription']['id'] ?? '';

		$response = array(
			'currency'       => 'USD',
			'products'       => $products,
			'subscriptionId' => $is_agency_subs || ! $is_regular_subs ? '' : $subs_id,
			'currentPlan'    => $site['subscription']['product'] ?? false,
		);
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Gets site information from either the cloud or cache.
	 *
	 * @param 'cache'|'cloud' $mode Where to retrieve the info from.
	 *
	 * @return TAWS_Site|WP_Error
	 */
	private function get_site( $mode ) {

		if ( 'cache' === $mode ) {
			/** @var TAWS_Site|false */
			$site = get_transient( 'nab_site_object' );
			if ( ! empty( $site ) && ! $this->is_site_outdated( $site ) ) {
				return $site;
			}
		}

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$site = nab_extract_response_body( $response );
		if ( is_wp_error( $site ) ) {
			delete_transient( 'nab_site_object' );
			return $site;
		}

		// Regenerate the account result and send it to the JS.
		/** @var TAWS_Site $site */
		$this->cache_site( $site );
		return $site;
	}

	/**
	 * This helper function creates an account object.
	 *
	 * @param TAWS_Site $site The data about the site.
	 *
	 * @return bool
	 *
	 * @since  8.1.7
	 */
	private function is_site_outdated( $site ) {
		$subs  = $site['subscription'] ?? array();
		$plan  = nab_get_plan( $subs['product'] ?? '' );
		$quota = absint( $subs['quota'] ?? 0 ) + absint( $subs['quotaExtra'] ?? 0 );
		return 'free' !== $plan && 100 > $quota;
	}

	/**
	 * This helper function creates an account object.
	 *
	 * @param TAWS_Site $site The data about the site.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function cache_site( $site ) {
		set_transient( 'nab_site_object', $site, HOUR_IN_SECONDS / 2 );


		/**
		 * Runs after storing the site data in cache.
		 *
		 * @param TAWS_Site $site the cached site.
		 *
		 * @since 6.4.0
		 */
		do_action( 'nab_site_updated', $site );
	}

	/**
	 * This helper function creates an account object.
	 *
	 * @param TAWS_Site $site The data about the site.
	 *
	 * @return TAccount
	 *
	 * @since  5.0.0
	 */
	private function create_account_object( $site ) {
		$avatar = get_avatar_url( $site['subscription']['account']['email'] ?? '', array( 'default' => 'mysteryman' ) );
		return array(
			'creationDate'        => $site['creation'] ?? '',
			'email'               => $site['subscription']['account']['email'] ?? '',
			'fullname'            => trim(
				sprintf(
				/* translators: %1$s: Firstname. %2$s: Lastname. */
					_x( '%1$s %2$s', 'text name', 'nelio-ab-testing' ),
					$site['subscription']['account']['firstname'] ?? '',
					$site['subscription']['account']['lastname'] ?? ''
				)
			),
			'firstname'           => $site['subscription']['account']['firstname'] ?? '',
			'lastname'            => $site['subscription']['account']['lastname'] ?? '',
			'photo'               => ! empty( $avatar ) ? $avatar : '',
			'mode'                => $site['subscription']['mode'] ?? 'free',
			'startDate'           => $site['subscription']['startDate'] ?? '',
			'license'             => $site['subscription']['license'] ?? '',
			'endDate'             => $site['subscription']['endDate'] ?? '',
			'nextChargeDate'      => $site['subscription']['nextChargeDate'] ?? '',
			'deactivationDate'    => $site['subscription']['deactivationDate'] ?? '',
			'nextChargeTotal'     => $site['subscription']['nextChargeTotalDisplay'] ?? '',
			'plan'                => nab_get_plan( $site['subscription']['product'] ?? '' ),
			'addons'              => $site['subscription']['addons'] ?? array(),
			'addonDetails'        => $site['subscription']['addonDetails'] ?? array(),
			'productId'           => $site['subscription']['product'] ?? '',
			'productDisplay'      => $site['subscription']['display'] ?? '',
			'state'               => $site['subscription']['state'] ?? 'active',
			'quota'               => absint( $site['subscription']['quota'] ?? 0 ),
			'quotaExtra'          => absint( $site['subscription']['quotaExtra'] ?? 0 ),
			'quotaPerMonth'       => absint( $site['subscription']['quotaPerMonth'] ?? 0 ),
			'currency'            => $site['subscription']['currency'] ?? 'USD',
			'sitesAllowed'        => $site['subscription']['sitesAllowed'] ?? 1,
			'period'              => $site['subscription']['intervalUnit'] ?? 'month',
			'subscription'        => $site['subscription']['id'] ?? '',
			'isAgency'            => ! empty( $site['subscription']['isAgency'] ),
			'urlToManagePayments' => nab_get_api_url( '/fastspring/' . ( $site['subscription']['id'] ?? '' ) . '/url', 'browser' ),
		);
	}

	/**
	 * Triggers the `nab_site_created` action.
	 *
	 * @return void
	 */
	private function notify_site_created() {

		/**
		 * Fires once the site has been registered in Nelio’s cloud.
		 *
		 * When fired, the site has a valid site ID and an API secret.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_site_created' );
	}

	/**
	 * Removes sensitive attributes if this is an agency account.
	 *
	 * @param TAccount $account The account.
	 *
	 * @return TAccount
	 */
	private function protect_agency_account( $account ) {

		if ( empty( $account['isAgency'] ) ) {
			return $account;
		}

		return array(
			'creationDate'        => '',
			'email'               => '',
			'fullname'            => '',
			'firstname'           => '',
			'lastname'            => '',
			'photo'               => '',
			'mode'                => $account['mode'],
			'startDate'           => '',
			'license'             => '',
			'endDate'             => '',
			'nextChargeDate'      => '',
			'deactivationDate'    => '',
			'nextChargeTotal'     => '',
			'plan'                => $account['plan'],
			'addons'              => array(),
			'addonDetails'        => array(),
			'productId'           => $account['productId'],
			'productDisplay'      => $account['productDisplay'],
			'state'               => $account['state'],
			'quota'               => $account['quota'],
			'quotaExtra'          => $account['quotaExtra'],
			'quotaPerMonth'       => 0,
			'currency'            => '',
			'sitesAllowed'        => 1,
			'period'              => $account['period'],
			'subscription'        => '',
			'isAgency'            => true,
			'urlToManagePayments' => '',
		);
	}
}
