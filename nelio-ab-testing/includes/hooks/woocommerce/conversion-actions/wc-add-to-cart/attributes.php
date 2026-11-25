<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Add_To_Cart;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes WooCommerce add-to-cart conversion action attributes.
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
				'anyProduct' => Z::boolean()->default( false )->catch( false ),
				'productId'  => Z::number()->transform( fn( $v ) => absint( $v ) )->default( 0 )->catch( 0 ),
			)
		)->catch(
			array(
				'anyProduct' => false,
				'productId'  => 0,
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/wc-add-to-cart_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
