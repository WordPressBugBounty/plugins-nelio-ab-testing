<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Add_To_Cart;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the WooCommerce add-to-cart conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/wc-add-to-cart'] = array(
		'name'                => 'nab/wc-add-to-cart',
		'category'            => 'ecommerce',
		'title'               => _x( 'Add WooCommerce product to cart', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts adding a WooCommerce product to the shopping cart as a conversion.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array(
			'anyProduct' => false,
			'productId'  => 0,
		),
		'supportsCustomScope' => true,
	);
	return $types;
}
