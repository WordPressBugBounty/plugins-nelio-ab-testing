<?php

namespace Nelio_AB_Testing\Compat\Google_Analytics_4;

defined( 'ABSPATH' ) || exit;

/**
 * Forwards a conversion event from Nelio A/B Testing to Google Analytics.
 *
 * @param TConversion_Event         $event   The event.
 * @param TConversion_Event_Options $options The options.
 *
 * @return void
 */
function maybe_track_ga4_conversion( $event, $options ) {
	if ( empty( $options['ga4_client_id'] ) ) {
		return;
	}

	$plugin_settings = \Nelio_AB_Testing_Settings::instance();
	$ga4_integration = $plugin_settings->get( 'google_analytics_tracking' );
	if ( empty( $ga4_integration['enabled'] ) ) {
		return;
	}

	$measurement_id = $ga4_integration['measurementId'];
	$api_secret     = $ga4_integration['apiSecret'];
	if ( empty( $measurement_id ) || empty( $api_secret ) ) {
		return;
	}

	$experiment = nab_get_experiment( $event['experiment'] );
	if ( is_wp_error( $experiment ) ) {
		return;
	}

	$summary = $experiment->summarize( true );

	$alternative = $summary['alternatives'][ $event['alternative'] ] ?? null;
	if ( ! $alternative ) {
		return;
	}

	$goal = $summary['goals'][ $event['goal'] ] ?? null;
	if ( ! $goal ) {
		return;
	}

	$alternative_name = $alternative['name'] ?? null;
	if ( ! $alternative_name ) {
		if ( 0 === $event['alternative'] ) {
			$alternative_name = 'Control';
		} else {
			$alternative_name = 'Variant ' . $event['alternative'];
		}
	}

	$payload = array(
		'client_id' => $options['ga4_client_id'],
		'events'    => array(
			array(
				'name'   => 'conversion',
				'params' => array(
					'experiment_id' => $event['experiment'],
					'variant_id'    => $event['alternative'],
					'variant_name'  => $alternative_name,
					'goal_id'       => $event['goal'],
					'goal_name'     => ! empty( $goal['name'] ) ? $goal['name'] : '',
					'value'         => ! empty( $event['value'] ) ? $event['value'] : 0,
				),
			),
		),
	);

	$url = add_query_arg(
		array(
			'measurement_id' => $measurement_id,
			'api_secret'     => $api_secret,
		),
		'https://www.google-analytics.com/mp/collect'
	);

	$body = wp_json_encode( $payload );
	if ( empty( $body ) ) {
		return;
	}

	wp_safe_remote_post(
		$url,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => $body,
			'timeout' => absint( apply_filters( 'nab_request_timeout', 30 ) ),
		)
	);
}
add_action( 'nab_after_tracking_conversion_event', __NAMESPACE__ . '\maybe_track_ga4_conversion', 10, 2 );
