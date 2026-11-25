<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Scroll;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes scroll conversion action attributes.
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
			array( 'value' => Z::number()->default( 50 )->catch( 50 ) )
		)->catch( array( 'value' => 50 ) );
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/scroll_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
