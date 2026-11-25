<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\User_Login;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes user-login segmentation rule attributes.
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
			array( 'condition' => Z::enum( array( 'is-logged-in', 'is-not-logged-in' ) )->default( 'is-logged-in' )->catch( 'is-logged-in' ) )
		)->catch( array( 'condition' => 'is-logged-in' ) );
	}
	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes $result */
	$result = $parsed['data'];
	return $result;
}
add_filter( 'nab_nab/user-login_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
