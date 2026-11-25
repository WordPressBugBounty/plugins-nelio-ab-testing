<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Video_Playback;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the video-playback conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/video-playback'] = array(
		'name'                => 'nab/video-playback',
		'category'            => 'interaction',
		'title'               => _x( 'YouTube Video Playback', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts starting or finishing playback of a selected YouTube video as a conversion.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array(
			'platform' => 'youtube',
			'event'    => 'play',
			'videoId'  => '',
		),
		'supportsCustomScope' => true,
	);
	return $types;
}
