<?php
/**
 * This file contains some functions to manage communication with Nelio’s cloud.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      8.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements some functions to manage communication with Nelio’s cloud.
 */
class Nelio_AB_Testing_Cloud {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pre_http_request', array( $this, 'prevent_unauthorized_requests' ), 10, 3 );
	}

	/**
	 * Prevents unauthorized requests to the Nelio AB_Testing API.
	 *
	 * @param mixed                $preempt Preempt value.
	 * @param array<string, mixed> $args    Arguments for the HTTP request.
	 * @param string               $url     The URL of the HTTP request.
	 *
	 * @return mixed
	 */
	public function prevent_unauthorized_requests( $preempt, $args, $url ) {
		if ( strpos( $url, 'https://api.nelioabtesting.com' ) !== 0 ) {
			return $preempt;
		}

		/** @var array{Authorization?: string} $headers */
		$headers       = $args['headers'] ?? array();
		$authorization = $headers['Authorization'] ?? '';
		if ( 'Bearer unauthorized' !== $authorization ) {
			return $preempt;
		}

		return new WP_Error( 'nelio-ab-testing-forbidden', '403 Forbidden', array( 'code' => 403 ) );
	}
}
