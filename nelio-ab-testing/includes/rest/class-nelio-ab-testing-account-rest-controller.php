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
	 * @var    Nelio_AB_Testing_Account_REST_Controller
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
	}//end register_routes()

	/**
	 * Retrieves this site’s quota.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_site_quota() {
		$site = $this->get_site( 'cache' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}//end if

		$subs_quota = absint( nab_array_get( $site, array( 'subscription', 'quota' ), 0 ) );
		$subs_extra = absint( nab_array_get( $site, array( 'subscription', 'quotaExtra' ), 0 ) );
		$subs_month = absint( nab_array_get( $site, array( 'subscription', 'quotaPerMonth' ), 1 ) );

		$site_used  = absint( nab_array_get( $site, 'usedMonthlyQuota', 0 ) );
		$site_month = absint( nab_array_get( $site, 'maxMonthlyQuota', 0 ) );

		$sub_product = nab_array_get( $site, array( 'subscription', 'product' ), '' );
		nab_update_subscription( nab_get_plan( $sub_product ) );

		$sub_addons = nab_array_get( $site, array( 'subscription', 'addons' ), array() );
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
	}//end get_site_quota()

	/**
	 * Retrieves this site’s quota.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_excluded_ips() {
		$site = $this->get_site( 'live' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}//end if

		return nab_array_get( $site, 'excludedIPs', array() );
	}//end get_excluded_ips()

	/**
	 * Retrieves information about the site.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_account_data() {

		$site = $this->get_site( 'live' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}//end if

		$account = $this->create_account_object( $site );
		nab_update_subscription( $account['plan'] );
		nab_update_subscription_addons( $account['addons'] );

		if ( 'OL-' === substr( $account['subscription'], 0, 3 ) ) {
			update_option( 'nab_is_subscription_deprecated', true );
		} else {
			delete_option( 'nab_is_subscription_deprecated' );
		}//end if

		$account = $this->protect_agency_account( $account );
		return new WP_REST_Response( $account, 200 );
	}//end get_account_data()

	/**
	 * Retrieves information about the site.
	 *
	 * @param WP_REST_Request $request Full data request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_agency_details( $request ) {

		$site = $this->get_site( 'cached' );
		if ( is_wp_error( $site ) ) {
			return $site;
		}//end if

		$account = $this->create_account_object( $site );
		$license = $request->get_param( 'license' );
		if ( $account['isAgency'] && $account['license'] !== $license ) {
			return new WP_Error(
				'invalid-license',
				_x( 'Invalid license code.', 'error', 'nelio-ab-testing' )
			);
		}//end if

		return new WP_REST_Response( $account, 200 );
	}//end get_agency_details()

	/**
	 * Creates a new free site in AWS and updates the info in WordPress.
	 *
	 * @return WP_REST_Response The response
	 */
	public function create_free_site() {

		$experiments_page = admin_url( 'edit.php?post_type=nab_experiment' );

		if ( nab_get_site_id() ) {
			return new WP_REST_Response( $experiments_page, 200 );
		}//end if

		$params = array(
			'id'         => nab_uuid(),
			'url'        => home_url(),
			'language'   => nab_get_language(),
			'timezone'   => nab_get_timezone(),
			'wpVersion'  => get_bloginfo( 'version' ),
			'nabVersion' => nelioab()->plugin_version,
		);

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => wp_json_encode( $params ),
		);

		$url      = nab_get_api_url( '/site', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );
		update_option( 'nab_site_id', $site_info['id'] );
		update_option( 'nab_api_secret', $site_info['secret'] );

		$this->notify_site_created();

		return new WP_REST_Response( $experiments_page, 200 );
	}//end create_free_site()

	/**
	 * Connects a site with a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function use_license_in_site( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['license'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'License key is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$license = trim( sanitize_text_field( $parameters['license'] ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( array( 'license' => $license ) ),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );
		$account   = $this->create_account_object( $site_info );

		nab_update_subscription( $account['plan'] );
		nab_update_subscription_addons( $account['addons'] );

		return new WP_REST_Response( $account, 200 );
	}//end use_license_in_site()

	/**
	 * Updates the quota limit of a site.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function update_quota_limit_of_site( $request ) {

		$parameters = $request->get_json_params();
		$site       = $request['id'];
		$params     = array(
			'maxMonthlyQuota' => $parameters['maxMonthlyQuota'],
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

		$url      = nab_get_api_url( '/site/' . $site, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );

		return new WP_REST_Response( $site_info, 200 );
	}//end update_quota_limit_of_site()

	/**
	 * Disconnects a site from a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function remove_license_from_site( $request ) {

		$site = $request['id'];

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

		$url      = nab_get_api_url( '/site/' . $site . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		if ( nab_get_site_id() === $site ) {
			nab_update_subscription( 'free' );
			nab_update_subscription_addons( array() );
		}//end if

		return new WP_REST_Response( 'OK', 200 );
	}//end remove_license_from_site()

	/**
	 * Upgrades a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function upgrade_subscription( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['product'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Plan is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$subscription = $request['id'];
		$product      = trim( sanitize_text_field( $parameters['product'] ) );
		$body         = array(
			'product'         => $product,
			'extraQuotaUnits' => absint( nab_array_get( $parameters, 'extraQuotaUnits', 0 ) ),
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
			'body'      => wp_json_encode( $body ),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );
	}//end upgrade_subscription()

	/**
	 * Cancels a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function cancel_subscription( $request ) {

		$subscription = $request['id'];

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

		$url      = nab_get_api_url( '/subscription/' . $subscription, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );
	}//end cancel_subscription()

	/**
	 * Un-cancels a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function uncancel_subscription( $request ) {

		$subscription = $request['id'];

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

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/uncancel', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );
	}//end uncancel_subscription()

	/**
	 * Buys additional quota for a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function buy_more_quota( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['quantity'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Quantity is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		if ( ! isset( $parameters['currency'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Currency is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$subscription = $request['id'];
		$quantity     = trim( sanitize_text_field( $parameters['quantity'] ) );
		$currency     = trim( sanitize_text_field( $parameters['currency'] ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode(
				array(
					'subscriptionId' => $subscription,
					'quantity'       => $quantity,
					'currency'       => $currency,
				)
			),
		);

		$url      = nab_get_api_url( '/fastspring/quota', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		$this->get_site( 'live' );
		return new WP_REST_Response( 'OK', 200 );
	}//end buy_more_quota()

	/**
	 * Obtains all sites connected with a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_sites_using_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
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
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result to send it to the JS.
		$sites = json_decode( $response['body'], true );

		// Move the current site to the top of the array of sites.
		$site_id                  = nab_get_site_id();
		$key                      = array_search( $site_id, array_column( $sites, 'id' ), true );
		$actual_site              = $sites[ $key ];
		$actual_site['actualUrl'] = home_url();
		array_splice( $sites, $key, 1 );
		array_unshift( $sites, $actual_site );

		$sites = array_map(
			function ( $site ) {
				$aux = array(
					'id'               => nab_array_get( $site, array( 'id' ) ),
					'url'              => nab_array_get( $site, array( 'url' ) ),
					'isCurrentSite'    => nab_get_site_id() === nab_array_get( $site, array( 'id' ) ),
					'maxMonthlyQuota'  => nab_array_get( $site, array( 'maxMonthlyQuota' ), -1 ),
					'usedMonthlyQuota' => nab_array_get( $site, array( 'usedMonthlyQuota' ), 0 ),
				);

				if ( $aux['isCurrentSite'] ) {
					$aux['actualUrl'] = nab_array_get( $site, array( 'actualUrl' ) );
				}//end if

				return $aux;
			},
			$sites
		);

		if ( ! current_user_can( 'manage_nab_account' ) ) {
			$sites = array_filter(
				$sites,
				function ( $s ) {
					return nab_array_get( $s, 'isCurrentSite', false );
				}
			);
		}//end if

		return new WP_REST_Response( $sites, 200 );
	}//end get_sites_using_subscription()

	/**
	 * Obtains the invoices of a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_invoices_from_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
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
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the invoices result and send it to the JS.
		$invoices = json_decode( $response['body'], true );
		$invoices = array_map(
			function ( $invoice ) {
				$invoice['chargeDate'] = gmdate( get_option( 'date_format' ), strtotime( $invoice['chargeDate'] ) );
				return $invoice;
			},
			$invoices
		);

		return new WP_REST_Response( $invoices, 200 );
	}//end get_invoices_from_subscription()

	/**
	 * Obtains fastspring related info (products, currency, etc)
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_fastspring_props() {
		$products = get_transient( 'nab_products' );
		if ( false === $products ) {
			$data = array(
				'method'    => 'GET',
				'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
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
			$error = nab_maybe_return_error_json( $response );
			if ( $error ) {
				return $error;
			}//end if

			// Regenerate the products result and send it to the JS.
			$products = json_decode( $response['body'], true );
			$products = array_map(
				function ( $product ) {
					$from = isset( $product['upgradeableFrom'] ) ? $product['upgradeableFrom'] : '';
					if ( ! is_array( $from ) ) {
						$from = empty( $from ) ? array() : array( $from );
					}//end if
					return array(
						'id'                => nab_array_get( $product, array( 'product' ) ),
						'plan'              => nab_array_get( $product, array( 'isAddon' ), true ) ? 'addon' : nab_get_plan( nab_array_get( $product, array( 'product' ) ) ),
						'period'            => nab_get_period( nab_array_get( $product, array( 'product' ) ) ),
						'displayName'       => nab_array_get( $product, array( 'display' ) ),
						'price'             => nab_array_get( $product, array( 'pricing', 'price' ) ),
						'quantityDiscounts' => nab_array_get( $product, array( 'pricing', 'quantityDiscounts' ), array() ),
						'description'       => nab_array_get( $product, array( 'description', 'full' ) ),
						'attributes'        => nab_array_get( $product, array( 'attributes' ), array() ),
						'isAddon'           => nab_array_get( $product, array( 'isAddon' ), true ),
						'isSubscription'    => nab_array_get( $product, array( 'isSubscription' ), true ),
						'upgradeableFrom'   => $from,
						'allowedAddons'     => nab_array_get( $product, array( 'allowedAddons' ), array() ),
					);
				},
				$products
			);

			set_transient( 'nab_products', $products, HOUR_IN_SECONDS );
		}//end if

		$site         = $this->get_site( 'cache' );
		$subscription = ! is_wp_error( $site ) ? nab_array_get( $site, 'subscription', array() ) : array();
		$subscription = is_array( $subscription ) ? $subscription : array();

		$is_agency_subs  = ! empty( nab_array_get( $subscription, 'isAgency', false ) );
		$is_regular_subs = 'regular' === nab_array_get( $subscription, 'mode' );
		$subs_id         = nab_array_get( $subscription, 'id', '' );

		$response = array(
			'currency'       => 'USD',
			'products'       => $products,
			'subscriptionId' => $is_agency_subs || ! $is_regular_subs ? '' : $subs_id,
			'currentPlan'    => nab_array_get( $subscription, 'product', false ),
		);
		return new WP_REST_Response( $response, 200 );
	}//end get_fastspring_props()

	private function get_site( $mode ) {

		if ( 'cache' === $mode ) {
			$site = get_transient( 'nab_site_object' );
			if ( ! empty( $site ) ) {
				return $site;
			}//end if
		}//end if

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
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
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			delete_transient( 'nab_site_object' );
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site = json_decode( $response['body'], true );
		$this->cache_site( $site );
		return $site;
	}//end get_site()

	private function cache_site( $site ) {
		set_transient( 'nab_site_object', $site, HOUR_IN_SECONDS / 2 );

		/**
		 * Runs after storing the site data in cache.
		 *
		 * @param array $site the cached site.
		 *
		 * @since 6.4.0
		 */
		do_action( 'nab_site_updated', $site );
	}//end cache_site()

	/**
	 * This helper function creates an account object.
	 *
	 * @param object $site The data about the site.
	 *
	 * @return array an account object.
	 *
	 * @since  5.0.0
	 */
	private function create_account_object( $site ) {

		return array(
			'creationDate'        => $site['creation'],
			'email'               => nab_array_get( $site, array( 'subscription', 'account', 'email' ) ),
			'fullname'            => sprintf(
				/* translators: 1 -> firstname, 2 -> lastname */
				_x( '%1$s %2$s', 'text name', 'nelio-ab-testing' ),
				nab_array_get( $site, array( 'subscription', 'account', 'firstname' ) ),
				nab_array_get( $site, array( 'subscription', 'account', 'lastname' ) )
			),
			'firstname'           => nab_array_get( $site, array( 'subscription', 'account', 'firstname' ) ),
			'lastname'            => nab_array_get( $site, array( 'subscription', 'account', 'lastname' ) ),
			'photo'               => get_avatar_url( nab_array_get( $site, array( 'subscription', 'account', 'email' ) ), array( 'default' => 'mysteryman' ) ),
			'mode'                => nab_array_get( $site, array( 'subscription', 'mode' ) ),
			'startDate'           => nab_array_get( $site, array( 'subscription', 'startDate' ) ),
			'license'             => nab_array_get( $site, array( 'subscription', 'license' ) ),
			'endDate'             => nab_array_get( $site, array( 'subscription', 'endDate' ) ),
			'nextChargeDate'      => nab_array_get( $site, array( 'subscription', 'nextChargeDate' ) ),
			'deactivationDate'    => nab_array_get( $site, array( 'subscription', 'deactivationDate' ) ),
			'nextChargeTotal'     => nab_array_get( $site, array( 'subscription', 'nextChargeTotalDisplay' ) ),
			'plan'                => nab_get_plan( nab_array_get( $site, array( 'subscription', 'product' ) ) ),
			'addons'              => nab_array_get( $site, array( 'subscription', 'addons' ) ),
			'addonDetails'        => nab_array_get( $site, array( 'subscription', 'addonDetails' ) ),
			'productId'           => nab_array_get( $site, array( 'subscription', 'product' ) ),
			'productDisplay'      => nab_array_get( $site, array( 'subscription', 'display' ) ),
			'state'               => nab_array_get( $site, array( 'subscription', 'state' ) ),
			'quota'               => nab_array_get( $site, array( 'subscription', 'quota' ) ),
			'quotaExtra'          => nab_array_get( $site, array( 'subscription', 'quotaExtra' ) ),
			'quotaPerMonth'       => nab_array_get( $site, array( 'subscription', 'quotaPerMonth' ) ),
			'currency'            => nab_array_get( $site, array( 'subscription', 'currency' ), 'USD' ),
			'sitesAllowed'        => nab_array_get( $site, array( 'subscription', 'sitesAllowed' ) ),
			'period'              => nab_array_get( $site, array( 'subscription', 'intervalUnit' ), 'month' ),
			'subscription'        => nab_array_get( $site, array( 'subscription', 'id' ) ),
			'isAgency'            => nab_array_get( $site, array( 'subscription', 'isAgency' ), false ),
			'urlToManagePayments' => nab_get_api_url( '/fastspring/' . nab_array_get( $site, array( 'subscription', 'id' ) ) . '/url', 'browser' ),
		);
	}//end create_account_object()

	private function notify_site_created() {

		/**
		 * Fires once the site has been registered in Nelio’s cloud.
		 *
		 * When fired, the site has a valid site ID and an API secret.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_site_created' );
	}//end notify_site_created()

	private function protect_agency_account( $account ) {

		if ( empty( $account['isAgency'] ) ) {
			return $account;
		}//end if

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
			'productId'           => $account['productId'],
			'productDisplay'      => $account['productDisplay'],
			'state'               => $account['state'],
			'quota'               => '',
			'quotaExtra'          => '',
			'quotaPerMonth'       => '',
			'currency'            => '',
			'sitesAllowed'        => 1,
			'period'              => $account['period'],
			'subscription'        => '',
			'isAgency'            => true,
			'urlToManagePayments' => '',
		);
	}//end protect_agency_account()
}//end class
