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
							'sanitize_callback' => fn( $v ) => trim( sanitize_text_field( $v ) ),
							'validate_callback' => fn( $v ) => ! empty( $v ),
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
					'args'                => array(
						'license' => array(
							'description'       => _x( 'License Key', 'text', 'nelio-ab-testing' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => trim( sanitize_text_field( $v ) ),
							'validate_callback' => fn( $v ) => ! empty( $v ),
						),
					),
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
					'args'                => array(
						'maxMonthlyQuota' => array(
							'type'              => 'number',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => is_numeric( $v ) ? intval( $v ) : -1,
							'validate_callback' => fn( $v ) => is_numeric( $v ) && -1 <= $v,
						),
					),
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
					'args'                => array(
						'product'         => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => trim( sanitize_text_field( $v ) ),
							'validate_callback' => fn( $v ) => ! empty( $v ),
						),
						'extraQuotaUnits' => array(
							'type'              => 'number',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => absint( $v ),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_subscription' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
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
					'args'                => array(
						'quantity' => array(
							'type'              => 'string|number',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => '' . absint( $v ),
							'validate_callback' => fn( $v ) => ! empty( $v ),
						),
						'currency' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => trim( sanitize_text_field( $v ) ),
							'validate_callback' => fn( $v ) => ! empty( $v ),
						),
					),
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
				),
			)
		);
	}

	/**
	 * Retrieves this site’s quota.
	 *
	 * @return array{mode:'site'|'subscription',availableQuota:int,percentage:int}|WP_Error
	 */
	public function get_site_quota() {
		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site; // @codeCoverageIgnore
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

		/** @var int */
		$available_quota = $site_month
			? max( 0, $site_month - $site_used )
			: max( 0, $subs_quota ) + max( 0, $subs_extra );

		$percentage = $site_month
			? floor( ( 100 * ( $available_quota + 0.1 ) ) / $site_month )
			: floor( ( 100 * ( $available_quota + 0.1 ) ) / $subs_month );

		return array(
			'mode'           => $site_month ? 'site' : 'subscription',
			'availableQuota' => $available_quota,
			'percentage'     => absint( min( $percentage, 100 ) ),
		);
	}

	/**
	 * Retrieves this site’s quota.
	 *
	 * @return array<string>|WP_Error
	 */
	public function get_excluded_ips() {
		$site = $this->get_site( 'cloud' );
		if ( is_wp_error( $site ) ) {
			return $site; // @codeCoverageIgnore
		}

		return $site['excludedIPs'] ?? array();
	}

	/**
	 * Retrieves information about the site.
	 *
	 * @return TAccount|WP_Error
	 */
	public function get_account_data() {

		$site = $this->get_site( 'cloud' );
		if ( is_wp_error( $site ) ) {
			return $site; // @codeCoverageIgnore
		}

		$account = $this->create_account_object( $site );
		nab_update_subscription( $account['plan'] );
		nab_update_subscription_addons( $account['addons'] );

		return $this->protect_agency_account( $account );
	}

	/**
	 * Retrieves information about the site.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data request.
	 *
	 * @return TAccount|WP_Error
	 */
	public function get_agency_details( $request ) {

		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site; // @codeCoverageIgnore
		}

		$account = $this->create_account_object( $site );
		$license = $request['license'];
		if ( $account['isAgency'] && $account['license'] !== $license ) {
			return new WP_Error(
				'invalid-license',
				_x( 'Invalid license code.', 'error', 'nelio-ab-testing' )
			);
		}

		return $account;
	}

	/**
	 * Creates a new free site in AWS and updates the info in WordPress.
	 *
	 * @return string|WP_Error
	 */
	public function create_free_site() {

		$experiments_page = admin_url( 'edit.php?post_type=nab_experiment' );

		if ( nab_get_site_id() ) {
			return $experiments_page;
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
		assert( ! empty( $body ) );

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
			return $body; // @codeCoverageIgnore
		}

		// Regenerate the account result and send it to the JS.
		$body = is_array( $body ) ? $body : array();
		if ( ! isset( $body['id'] ) || ! isset( $body['secret'] ) ) {
			return new WP_Error( 'invalid-result-type', _x( 'Invalid result type.', 'text', 'nelio-ab-testing' ) );
		}

		update_option( 'nab_site_id', $body['id'] );
		update_option( 'nab_api_secret', $body['secret'] );

		$this->notify_site_created();

		return $experiments_page;
	}

	/**
	 * Connects a site with a subscription.
	 *
	 * @param WP_REST_Request<array<license,mixed>> $request Full data about the request.
	 *
	 * @return TAccount|WP_Error
	 */
	public function use_license_in_site( $request ) {

		$license = $request['license'];

		$body = wp_json_encode( array( 'license' => $license ) );
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

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$site_info = nab_extract_response_body( $response );
		if ( is_wp_error( $site_info ) ) {
			return $site_info; // @codeCoverageIgnore
		}

		/** @var TAWS_Site $site_info */
		$account = $this->create_account_object( $site_info );
		nab_update_subscription( $account['plan'] );
		nab_update_subscription_addons( $account['addons'] );

		return $account;
	}

	/**
	 * Updates the quota limit of a site.
	 *
	 * @param WP_REST_Request<array{id:string,maxMonthlyQuota:int}> $request Full data about the request.
	 *
	 * @return TSite|WP_Error
	 */
	public function update_quota_limit_of_site( $request ) {

		$site   = $request['id'];
		$params = array( 'maxMonthlyQuota' => $request['maxMonthlyQuota'] );

		$body = wp_json_encode( $params );
		assert( ! empty( $body ) );

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
			return $site_info; // @codeCoverageIgnore
		}

		/** @var TSite */
		return $site_info;
	}

	/**
	 * Disconnects a site from a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 * @return 'OK'|WP_Error
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
			return $response; // @codeCoverageIgnore
		}

		if ( nab_get_site_id() === $site ) {
			nab_update_subscription( 'free' );
			nab_update_subscription_addons( array() );
		}

		return 'OK';
	}

	/**
	 * Upgrades a subscription.
	 *
	 * @param WP_REST_Request<array{id:string,product:string,extraQuotaUnits:number}> $request Full data about the request.
	 *
	 * @return 'OK'|WP_Error
	 */
	public function upgrade_subscription( $request ) {

		$subscription = $request['id'];
		$params       = array(
			'product'         => $request['product'],
			'extraQuotaUnits' => $request['extraQuotaUnits'],
		);

		$body = wp_json_encode( $params );
		assert( ! empty( $body ) );

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
			return $response; // @codeCoverageIgnore
		}

		return 'OK';
	}

	/**
	 * Cancels a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 *
	 * @return 'OK'|WP_Error
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
			return $response; // @codeCoverageIgnore
		}

		return 'OK';
	}

	/**
	 * Un-cancels a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 *
	 * @return 'OK'|WP_Error
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
			return $response; // @codeCoverageIgnore
		}

		return 'OK';
	}

	/**
	 * Buys additional quota for a subscription.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return 'OK'|WP_Error
	 */
	public function buy_more_quota( $request ) {

		$subscription = $request['id'];
		$quantity     = $request['quantity'];
		$currency     = $request['currency'];

		$params = array(
			'subscriptionId' => $subscription,
			'quantity'       => $quantity,
			'currency'       => $currency,
		);

		$body = wp_json_encode( $params );
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

		$url      = nab_get_api_url( '/fastspring/quota', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response; // @codeCoverageIgnore
		}

		$this->get_site( 'cloud' );
		return 'OK';
	}

	/**
	 * Obtains all sites connected with a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 *
	 * @return list<array{id:string,url:string,isCurrentSite:bool,maxMonthlyQuota:int,usedMonthlyQuota:int,actualUrl?:string}>|WP_Error
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
			return $sites; // @codeCoverageIgnore
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

		return $sites;
	}

	/**
	 * Obtains the invoices of a subscription.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 *
	 * @return list<TInvoice>|WP_Error
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
			return $invoices; // @codeCoverageIgnore
		}

		// Regenerate the invoices result and send it to the JS.
		$invoices = is_array( $invoices ) ? $invoices : array();
		/** @var list<TInvoice> */
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

		return $invoices;
	}

	/**
	 * Obtains fastspring related info (products, currency, etc)
	 *
	 * @return array{currency:string,products:list<TFS_Product>,subscriptionId:string,currentPlan:string|false}|WP_Error
	 */
	public function get_fastspring_props() {
		/** @var list<TFS_Product>|false */
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
				return $products; // @codeCoverageIgnore
			}

			/** @var list<TAWS_Product> */
			$products = is_array( $products ) ? $products : array();
			$products = array_map( fn( $v ) => $this->convert_aws_product_to_product( $v ), $products );
			set_transient( 'nab_products', $products, HOUR_IN_SECONDS );
		}

		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site; // @codeCoverageIgnore
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
		return $response;
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
			if ( ! empty( $site ) ) {
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
			return $site; // @codeCoverageIgnore
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
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function cache_site( $site ) {
		$subs     = $site['subscription'] ?? array();
		$quota    = absint( $subs['quota'] ?? 0 ) + absint( $subs['quotaExtra'] ?? 0 );
		$lifetime = 100 > $quota ? 5 : 30;
		set_transient( 'nab_site_object', $site, $lifetime * MINUTE_IN_SECONDS );


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

	/**
	 * Converts AWS product to custom product.
	 *
	 * @param TAWS_Product $product Product.
	 *
	 * @return TFS_Product
	 */
	private function convert_aws_product_to_product( $product ) {
		$from = isset( $product['upgradeableFrom'] ) ? $product['upgradeableFrom'] : '';
		if ( ! is_array( $from ) ) {
			$from = empty( $from ) ? array() : array( $from );
		}

		$empty_i18n_string = array(
			'es' => '',
			'en' => '',
		);

		return array(
			'id'                => $product['product'] ?? '',
			'allowedAddons'     => $product['allowedAddons'] ?? array(),
			'attributes'        => $product['attributes'] ?? array(),
			'description'       => $product['description']['full'] ?? $empty_i18n_string,
			'displayName'       => $product['display'] ?? $empty_i18n_string,
			'isAddon'           => ! empty( $product['isAddon'] ),
			'isSubscription'    => ! empty( $product['isSubscription'] ),
			'period'            => nab_get_period( $product['product'] ?? '' ),
			'price'             => $product['pricing']['price'] ?? array(),
			'quantityDiscounts' => $product['pricing']['quantityDiscounts'] ?? array(),
			'upgradeableFrom'   => $from,
		);
	}
}
