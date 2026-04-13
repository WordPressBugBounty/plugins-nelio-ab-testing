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
	 * @return 'OK'|WP_Error
	 */
	public function activate_recordings() {
		// @codeCoverageIgnoreStart
		if ( ! nab_is_subscribed_to_addon( 'nsr-addon' ) ) {
			$response = $this->subscribe_to_addon( 'nsr-addon' );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			delete_option( 'neliosr_standalone' );
		}

		return $this->activate_plugin( 'nelio-session-recordings/nelio-session-recordings.php' );
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Activates the given plugin if user has proper permissions and plugin is not already active.
	 *
	 * @param string $plugin_slug Plugin slug.
	 *
	 * @return 'OK'|WP_Error
	 */
	private function activate_plugin( $plugin_slug ) {
		// @codeCoverageIgnoreStart
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

		if ( is_plugin_active( $plugin_slug ) ) {
			return 'OK';
		}

		$installed_plugins = get_plugins();
		if ( array_key_exists( $plugin_slug, $installed_plugins ) ) {
			$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $plugin_slug, '', false, false );
			if ( ! is_wp_error( $activated ) ) {
				return 'OK';
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
				'slug'   => explode( '/', $plugin_slug )[0],
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

		return 'OK';
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Subscribes to the given addon.
	 *
	 * @param string $addon_name Addon’s name.
	 *
	 * @return WP_Error|true
	 */
	private function subscribe_to_addon( $addon_name ) {
		// @codeCoverageIgnoreStart
		$params = array(
			'siteId' => nab_get_site_id(),
			'addon'  => $addon_name,
		);
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
		// @codeCoverageIgnoreEnd
	}
}
