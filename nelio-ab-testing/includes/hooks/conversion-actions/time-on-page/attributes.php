<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Time_On_Page;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes time-on-page conversion action attributes.
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
			array( 'value' => Z::number()->default( 20 )->catch( 20 ) )
		)->catch( array( 'value' => 20 ) );
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/time-on-page_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
