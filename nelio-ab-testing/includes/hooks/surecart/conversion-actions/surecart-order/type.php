<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the SureCart order conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/surecart-order'] = array(
		'name'             => 'nab/surecart-order',
		'category'         => 'ecommerce',
		'title'            => _x( 'SureCart Purchase', 'text', 'nelio-ab-testing' ),
		'description'      => _x( 'Counts a SureCart purchase matching a product selection as a conversion.', 'text', 'nelio-ab-testing' ),
		'isPurchaseAction' => true,
		'defaults'         => array(
			'type'  => 'surecart-selection',
			'value' => array( 'type' => 'all-surecart-products' ),
		),
	);
	return $types;
}
