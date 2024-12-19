<?php

namespace Nelio_AB_Testing\Compat\Divi;

defined( 'ABSPATH' ) || exit;

function remove_divi_loop_hooks_during_rest_request( $response, $handler, $request ) {

	$route = $request->get_route();
	if ( 0 !== strpos( $route, '/nab/' ) ) {
		return $response;
	}//end if

	remove_action( 'loop_start', 'et_dbp_main_loop_start' );
	remove_action( 'loop_end', 'et_dbp_main_loop_end' );

	return $response;
}//end remove_divi_loop_hooks_during_rest_request()

add_action(
	'plugins_loaded',
	function () {
		// Notice: these hooks must be enabled ALWAYS, because during `plugins_loaded`
		// we can't check if Divi theme is active and, if it is, we need them.
		add_action( 'rest_request_before_callbacks', __NAMESPACE__ . '\remove_divi_loop_hooks_during_rest_request', 10, 3 );
	}
);
