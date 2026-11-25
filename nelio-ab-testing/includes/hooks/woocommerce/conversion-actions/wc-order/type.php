<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the WooCommerce order conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/wc-order'] = array(
		'name'               => 'nab/wc-order',
		'category'           => 'ecommerce',
		'title'              => _x( 'WooCommerce Purchase', 'text', 'nelio-ab-testing' ),
		'description'        => _x( 'Counts a WooCommerce purchase matching a product selection as a conversion.', 'text', 'nelio-ab-testing' ),
		'isPurchaseAction'   => true,
		'ecommercePlugin'    => 'woocommerce',
		'requiresUniformity' => true,
		'defaults'           => array(
			'type'  => 'product-selection',
			'value' => array( 'type' => 'all-products' ),
		),
	);
	return $types;
}
