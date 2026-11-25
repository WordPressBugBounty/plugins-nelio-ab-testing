<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Visitor_Type;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes visitor-type segmentation rule attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes Sanitized attributes.
 */
function sanitize( $attributes ) {
	/** @var Schema|null $schema */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array( 'condition' => Z::enum( array( 'is-new-visitor', 'is-returning-visitor' ) )->default( 'is-new-visitor' )->catch( 'is-new-visitor' ) )
		)->catch( array( 'condition' => 'is-new-visitor' ) );
	}
	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes $result */
	$result = $parsed['data'];
	return $result;
}
add_filter( 'nab_nab/visitor-type_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
