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
 * @param string $method  The metho we want to use.
 * @param string $context Either 'wp' or 'browser', depending on the location
 *                        in which the resulting URL has to be used.
 *                        Only wp calls might use the proxy URL.
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

	if ( nab_is_staging() ) {
		return;
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

	$events  = wp_json_encode( $events );
	$site_id = nab_get_site_id();
	if ( empty( $events ) || empty( $site_id ) ) {
		return;
	}

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
 * @param string $mode Either 'regular' or 'skip-errors'. If the latter is used, the function
 *                     won't generate any HTML errors.
 *
 * @return string a new token for accessing the API.
 *
 * @since 5.0.0
 */
function nab_generate_api_auth_token( $mode = 'regular' ) {

	/** @var string */
	static $token;

	if ( ! nab_get_site_id() ) {
		return '';
	}

	// If we already have a token, return it.
	if ( ! empty( $token ) ) {
		return $token;
	}

	// If we don't, let's see if there's a transient.
	$transient_name     = 'nab_api_token_' . get_current_user_id();
	$token              = get_transient( $transient_name );
	$transient_exp_date = get_option( '_transient_timeout_' . $transient_name );

	if ( ! empty( $transient_exp_date ) && ! empty( $token ) && is_string( $token ) ) {
		return $token;
	}

	// If we don't have a token, let's get a new one.
	$uid    = get_current_user_id();
	$role   = 'editor';
	$secret = nab_get_api_secret();

	$token = '';

	$body = wp_json_encode(
		array(
			'id'   => absint( $uid ),
			'role' => $role,
			'auth' => md5( $uid . $role . $secret ),
		)
	);
	if ( empty( $body ) ) {
		return $token;
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

	$nab_plan = 'free';

	// Iterate to obtain the token, or else things will go wrong.
	$url = nab_get_api_url( '/site/' . nab_get_site_id() . '/key', 'wp' );
	for ( $i = 0; $i < 3; ++$i ) {

		$response = wp_remote_request( $url, $data );
		$response = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			sleep( 3 );
			continue;
		}

		/** @var array{token:string, product?:string}|null $response */
		if ( empty( $response ) ) {
			sleep( 3 );
			continue;
		}

		// Get the new token.
		$token = $response['token'];

		// Get current plan.
		$nab_plan = nab_get_plan( isset( $response['product'] ) ? $response['product'] : '' );

		if ( ! empty( $token ) ) {
			break;
		}

		sleep( 3 );

	}

	if ( ! empty( $token ) ) {
		set_transient( $transient_name, $token, 25 * MINUTE_IN_SECONDS );
		nab_update_subscription( $nab_plan );
	}

	// Send error if we couldn't get an API key.
	if ( 'skip-errors' !== $mode ) {

		if ( empty( $token ) ) {

			if ( wp_doing_ajax() ) {
				header( 'HTTP/1.1 500 Internal Server Error' );
				wp_send_json( _x( 'There was an error while accessing Nelio A/B Testing’s API.', 'error', 'nelio-ab-testing' ) );
			} else {
				return '';
			}
		}
	}

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
	$body = is_string( $response['body'] ) ? $response['body'] : '{}';
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
	$response = $response['response'];
	$response = is_array( $response ) ? $response : array();

	$code    = isset( $response['code'] ) ? absint( $response['code'] ) : 0;
	$message = isset( $response['message'] ) && is_string( $response['message'] ) ? $response['message'] : '';
	$summary = "{$code} {$message}";
	if ( false === preg_match( '/^HTTP\/1.1 [0-9][0-9][0-9]( [A-Z][a-z]+)+$/', 'HTTP/1.1 ' . $summary ) ) {
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
