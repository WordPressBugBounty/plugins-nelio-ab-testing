<?php
/**
 * This file contains the class that defines REST API endpoints for
 * installing plugins in the background.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      6.4.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Plugin_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  6.4.0
	 * @var    Nelio_AB_Testing_Plugin_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Plugin_REST_Controller the single instance of this class.
	 *
	 * @since 6.4.0
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
	 * @since 6.4.0
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
			'/activate/recordings',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_recordings' ),
					'permission_callback' => nab_capability_checker( 'manage_nab_account' ),
				),
			)
		);
	}

	/**
	 * Installs and activates Nelio Session Recordings.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function activate_recordings() {

		if ( ! nab_is_subscribed_to_addon( 'nsr-addon' ) ) {
			$response = $this->subscribe_to_addon( 'nsr-addon' );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			delete_option( 'neliosr_standalone' );
		}

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'You do not have permission to perform this action.', 'text', 'nelio-ab-testing' )
			);
		}

		nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		nab_require_wp_file( '/wp-admin/includes/admin.php' );
		nab_require_wp_file( '/wp-admin/includes/plugin-install.php' );
		nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		nab_require_wp_file( '/wp-admin/includes/class-wp-upgrader.php' );
		nab_require_wp_file( '/wp-admin/includes/class-plugin-upgrader.php' );

		$plugin_slug = 'nelio-session-recordings/nelio-session-recordings.php';
		if ( is_plugin_active( $plugin_slug ) ) {
			return new WP_REST_Response( 'OK', 200 );
		}

		$installed_plugins = get_plugins();
		if ( array_key_exists( $plugin_slug, $installed_plugins ) ) {
			$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $plugin_slug, '', false, false );
			if ( ! is_wp_error( $activated ) ) {
				return new WP_REST_Response( 'OK', 200 );
			} else {
				return new WP_Error(
					'internal-error',
					_x( 'Error activating plugin.', 'text', 'nelio-ab-testing' )
				);
			}
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $this->get_plugin_dir( $plugin_slug ),
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'The requested plugin could not be installed. Plugin API call failed.', 'text', 'nelio-ab-testing' )
			);
		}

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   =
			is_object( $api ) && property_exists( $api, 'download_link' ) && is_string( $api->download_link )
				? $upgrader->install( $api->download_link )
				: null;
		if ( ! $result || is_wp_error( $result ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error installing plugin.', 'text', 'nelio-ab-testing' )
			);
		}

		$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $plugin_slug, '', false, true );
		if ( is_wp_error( $activated ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error activating plugin.', 'text', 'nelio-ab-testing' )
			);
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Subscribes to the given addon.
	 *
	 * @param string $addon_name Addon’s name.
	 *
	 * @return WP_Error|true
	 */
	private function subscribe_to_addon( $addon_name ) {
		$params = array(
			'siteId' => nab_get_site_id(),
			'addon'  => $addon_name,
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

		$url      = nab_get_api_url( '/fastspring/addon', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$addons = nab_get_subscription_addons();
		nab_update_subscription_addons( array_merge( $addons, array( $addon_name ) ) );
		return true;
	}

	/**
	 * Return’s plugin directory name.
	 *
	 * @param string $plugin Plugin name.
	 *
	 * @return string
	 */
	private function get_plugin_dir( $plugin ) {
		return explode( '/', $plugin )[0];
	}
}
