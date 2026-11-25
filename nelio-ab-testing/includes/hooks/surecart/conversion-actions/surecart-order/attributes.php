<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes SureCart order conversion action attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function sanitize( $attributes ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$products = Z::object(
			array(
				'type'       => Z::literal( 'surecart-ids' ),
				'productIds' => Z::array( Z::string()->trim()->catch( '' ) )->catch( array() ),
				'mode'       => Z::union( array( Z::literal( 'or' ), Z::literal( 'and' ) ) )->default( 'or' )->catch( 'or' ),
				'excluded'   => Z::boolean()->optional()->catch( null ),
			)
		)->catch(
			array(
				'type'       => 'surecart-ids',
				'productIds' => array(),
				'mode'       => 'or',
			)
		);
		$schema   = Z::object(
			array(
				'type'  => Z::literal( 'surecart-selection' )->default( 'surecart-selection' )->catch( 'surecart-selection' ),
				'value' => Z::union(
					array(
						Z::object( array( 'type' => Z::literal( 'all-surecart-products' ) ) ),
						Z::object(
							array(
								'type'  => Z::literal( 'some-surecart-products' ),
								'value' => $products,
							)
						),
					)
				)->default( array( 'type' => 'all-surecart-products' ) )->catch( array( 'type' => 'all-surecart-products' ) ),
			)
		)->catch(
			array(
				'type'  => 'surecart-selection',
				'value' => array( 'type' => 'all-surecart-products' ),
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/surecart-order_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
