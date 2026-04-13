<?php
/**
 * This file contains several helper functions that deal with the AWS API.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to use Nelio’s proxy instead of accessing AWS directly or not.
 *
 * @return boolean whether to use Nelio's proxy instead of accessing AWS directly or not.
 *
 * @since 5.0.0
 */
function nab_does_api_use_proxy() {

	/**
	 * Whether the plugin should use Nelio’s proxy instead of accessing AWS directly.
	 *
	 * @param boolean $uses_proxy use Nelio’s proxy instead of accessing AWS directly. Default: `false`.
	 *
	 * @since 5.0.0
	 */
	return apply_filters( 'nab_use_nelio_proxy', false );
}

/**
 * Returns the API url for the specified method.
 *
 * @param string         $method  The metho we want to use.
 * @param 'wp'|'browser' $context Either 'wp' or 'browser', depending on the location
 *                                in which the resulting URL has to be used.
 *                                Only wp calls might use the proxy URL.
 *
 * @return string the API url for the specified method.
 *
 * @since 5.0.0
 */
function nab_get_api_url( $method, $context ) {

	if ( 'browser' === $context ) {
		return 'https://api.nelioabtesting.com/v1' . $method;
	}

	if ( nab_does_api_use_proxy() ) {
		return 'https://neliosoftware.com/proxy/testing-api/v1' . $method;
	} else {
		return 'https://api.nelioabtesting.com/v1' . $method;
	}
}

/**
 * Sends a conversion to our cloud.
 *
 * @param int                       $experiment  Experiment ID.
 * @param int                       $goal        Goal index that contains the conversion action that triggered the conversion.
 * @param int|false                 $alternative The index of the alternative seen by the visitor that resulted in a conversion.
 * @param TConversion_Event_Options $options     Optional. Array that may include `value`, `segments`, and `unique_id`.
 *
 * @return void
 *
 * @since 5.0.0
 * @since 5.1.0 Add `$value` param.
 * @since 6.0.4 Change last param into `$options` array that accepts `value` and `unique_id`.
 * @since 6.4.1 Add `segments` to `$options`.
 */
function nab_track_conversion( $experiment, $goal, $alternative, $options = array() ) {

	$site_id = nab_get_site_id();
	if ( empty( $site_id ) ) {
		return; // @codeCoverageIgnore
	}

	if ( nab_is_staging() ) {
		return; // @codeCoverageIgnore
	}

	if ( false === $alternative ) {
		return;
	}

	$segments = isset( $options['segments'] ) ? $options['segments'] : array();
	$segments = array_map( 'absint', $segments );
	$segments = array_values( array_unique( $segments ) );
	$segments = ! in_array( 0, $segments, true ) ? array_merge( array( 0 ), $segments ) : $segments;

	$event = array(
		'id'          => nab_uuid(),
		'kind'        => 'conversion',
		'experiment'  => $experiment,
		'alternative' => $alternative,
		'goal'        => $goal,
		'segments'    => $segments,
		'timezone'    => nab_get_timezone(),
		'timestamp'   => str_replace( '+00:00', '.000Z', gmdate( 'c' ) ),
	);

	$value = empty( $options['value'] ) ? 0 : $options['value'];
	$value = is_string( $value ) ? trim( $value ) : $value;
	$value = is_numeric( $value ) ? abs( 0 + $value ) : 0;
	if ( ! empty( $value ) ) {
		$event['value'] = $value;
	}

	$events = isset( $options['unique_id'] ) ?
		array(
			$event,
			wp_parse_args(
				array(
					'id'   => $options['unique_id'] . '-' . $goal,
					'kind' => 'uconversion',
				),
				$event
			),
		) : array( $event );

	$events = wp_json_encode( $events );
	assert( ! empty( $events ) );

	$url = nab_get_api_url( '/site/' . nab_get_site_id() . '/event', 'wp' );
	$url = add_query_arg(
		array(
			'e' => rawurlencode( base64_encode( $events ) ),
			'a' => rawurlencode( $site_id ),
		),
		$url
	);

	$args = array(
		'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
	);

	wp_safe_remote_get( $url, $args );

	/**
	 * Firest after a conversion event has been synced with Nelio’s cloud.
	 *
	 * @param TConversion_Event         $event   Event.
	 * @param TConversion_Event_Options $options Options.
	 */
	do_action( 'nab_after_tracking_conversion_event', $event, $options );
}

/**
 * Returns a new token for accessing the API.
 *
 * @param int $attempts Number of recursive attempts. Default: `3`. Max: `3`.
 *
 * @return string a new token for accessing the API.
 *
 * @since 5.0.0
 */
function nab_generate_api_auth_token( $attempts = 3 ) {

	$attempts = 3 < $attempts ? 3 : $attempts;
	if ( --$attempts < 0 ) {
		return ''; // @codeCoverageIgnore
	}

	if ( ! nab_get_site_id() ) {
		return '';
	}

	// If we have it as a transient, use it.
	$transient_name = 'nab_api_token_' . get_current_user_id();
	$token          = get_transient( $transient_name );
	if ( ! empty( $token ) && is_string( $token ) ) {
		return $token;
	}

	// If we don't have a token, let's get a new one.
	$uid    = get_current_user_id();
	$role   = 'editor';
	$secret = nab_get_api_secret();

	$body = wp_json_encode(
		array(
			'id'   => absint( $uid ),
			'role' => $role,
			'auth' => md5( $uid . $role . $secret ),
		)
	);
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

	$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/key', 'wp' );
	$response = wp_remote_request( $url, $data );
	/** @var WP_Error|array{token:string, product?:string}|null $response */
	$response = nab_extract_response_body( $response );

	$token    = is_wp_error( $response ) ? '' : $response['token'] ?? '';
	$nab_plan = is_wp_error( $response ) ? 'free' : nab_get_plan( $response['product'] ?? '' );

	// @codeCoverageIgnoreStart
	// Recursive call to run multiple attempts.
	if ( empty( $token ) && ! empty( $attempts ) ) {
		sleep( 3 );
		return nab_generate_api_auth_token( $attempts );
	}
	// @codeCoverageIgnoreEnd

	set_transient( $transient_name, $token, 25 * MINUTE_IN_SECONDS );
	nab_update_subscription( $nab_plan );
	return $token;
}

/**
 * Returns the error message associated to the given code.
 *
 * @param string       $code          API error code.
 * @param string|false $default_value Optional. Default error message.
 *
 * @return string|false
 *
 * @since  5.0.0
 */
function nab_get_error_message( $code, $default_value = false ) {

	switch ( $code ) {

		case 'LicenseNotFound':
			return _x( 'Invalid license code.', 'error', 'nelio-ab-testing' );

		default:
			return $default_value;

	}
}

/**
 * This function converts a remote request response into either a WP_Error
 * object (if something failed) or whatever the original response had in its body.
 *
 * @param array<string,mixed>|WP_Error $response the response of a `wp_remote_*` call.
 *
 * @return mixed|WP_Error
 *
 * @since 5.0.0
 */
function nab_extract_response_body( $response ) {
	// If we couldn't open the page, let's return an empty result object.
	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'server-error',
			_x( 'Unable to access Nelio A/B Testing’s API.', 'text', 'nelio-ab-testing' )
		);
	}

	// Extract body and response.
	$body = ! empty( $response['body'] ) && is_string( $response['body'] ) ? $response['body'] : '{}';
	$body = json_decode( $body, true );
	$body = ! empty( $body ) ? $body : array();

	// Check if the API returned an error code and error message.
	if ( is_array( $body ) && isset( $body['errorType'] ) && isset( $body['errorMessage'] ) ) {
		$error_type    = is_string( $body['errorType'] ) && ! empty( $body['errorType'] ) ? $body['errorType'] : 'unknown-error';
		$error_message = is_string( $body['errorMessage'] ) && ! empty( $body['errorMessage'] ) ? $body['errorMessage'] : false;
		$error_message = nab_get_error_message( $error_type, $error_message );
		$error_message = ! empty( $error_message ) ? $error_message : _x( 'There was an error while accessing Nelio A/B Testing’s API.', 'error', 'nelio-ab-testing' );
		return new WP_Error( $error_type, $error_message );
	}

	// If we timed out, let the user know.
	$message = is_array( $body ) ? ( $body['message'] ?? '' ) : '';
	if ( 'Endpoint request timed out' === $message ) {
		return new WP_Error( 'nelio-api-timeout', _x( 'Nelio’s API timed out', 'text', 'nelio-ab-testing' ) );
	}

	// If the error is not an Unauthorized request, let's forward it to the user.
	$response = ! empty( $response['response'] ) ? $response['response'] : array();
	$response = is_array( $response ) ? $response : array();

	$code    = isset( $response['code'] ) ? absint( $response['code'] ) : 0;
	$message = isset( $response['message'] ) && is_string( $response['message'] ) ? $response['message'] : '';
	$summary = "{$code} {$message}";
	if ( ! preg_match( '/^HTTP\/1.1 [0-9][0-9][0-9]( [A-Z][a-z]+)+$/', 'HTTP/1.1 ' . $summary ) ) {
		$summary = '500 Internal Server Error';
	}

	if ( 200 !== $code ) {
		return new WP_Error(
			'server-error',
			sprintf(
			/* translators: %s: The placeholder is a string explaining the error returned by the API. */
				_x( 'There was an error while accessing Nelio A/B Testing’s API: %s.', 'error', 'nelio-ab-testing' ),
				$summary
			)
		);
	}

	return $body;
}

/**
 * Returns the API secret.
 *
 * @return string the API secret.
 *
 * @since 5.0.0
 */
function nab_get_api_secret() {
	return get_option( 'nab_api_secret', '' );
}
