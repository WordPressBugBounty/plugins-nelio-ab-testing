<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Video_Playback;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/video-playback conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when video engagement is the desired outcome.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'platform' => array(
					'type'        => 'string',
					'const'       => 'youtube',
					'description' => 'Video platform being tracked.',
				),
				'event'    => array(
					'type'        => 'string',
					'enum'        => array( 'play', 'end' ),
					'description' => 'Playback event that counts as a conversion.',
				),
				'videoId'  => array(
					'type'        => 'string',
					'description' => 'Identifier of the video being tracked.',
				),
			),
			'required'             => array( 'platform', 'event', 'videoId' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/video-playback_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates video-playback conversion action attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $action ) {
	$attributes = $action['attributes'];
	$sanitized  = sanitize( $attributes );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );
	$video_id   = is_string( $attributes['videoId'] ?? null ) ? $attributes['videoId'] : '';
	if ( '' === $video_id || ( 'youtube' === $attributes['platform'] && ! preg_match( '/^[-\w]{11}$/', $video_id ) ) ) {
		$issues[] = 'A valid 11-character YouTube video ID is required.';
	}
	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/video-playback_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
