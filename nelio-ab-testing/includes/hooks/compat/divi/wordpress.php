<?php

namespace Nelio_AB_Testing\Compat\Divi;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to remove divi l oop hooks during REST request.
 *
 * @param \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response Response.
 * @param TIgnore                                             $handler  Handler.
 * @param \WP_REST_Request<array<mixed>>                      $request  Request.
 *
 * @return \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed
 */
function remove_divi_loop_hooks_during_rest_request( $response, $handler, $request ) {

	$route = $request->get_route();
	if ( 0 !== strpos( $route, '/nab/' ) ) {
		return $response;
	}

	remove_action( 'loop_start', 'et_dbp_main_loop_start' );
	remove_action( 'loop_end', 'et_dbp_main_loop_end' );

	return $response;
}

add_action(
	'plugins_loaded',
	function () {
		// Notice: these hooks must be enabled ALWAYS, because during `plugins_loaded`
		// we can't check if Divi theme is active and, if it is, we need them.
		add_filter( 'rest_request_before_callbacks', __NAMESPACE__ . '\remove_divi_loop_hooks_during_rest_request', 10, 3 );
	}
);
