<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Video_Playback;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes video-playback conversion action attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function sanitize( $attributes ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'platform' => Z::literal( 'youtube' )->default( 'youtube' )->catch( 'youtube' ),
				'event'    => Z::union( array( Z::literal( 'play' ), Z::literal( 'end' ) ) )->default( 'play' )->catch( 'play' ),
				'videoId'  => Z::string()->trim()->catch( '' ),
			)
		)->catch(
			array(
				'platform' => 'youtube',
				'event'    => 'play',
				'videoId'  => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/video-playback_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
