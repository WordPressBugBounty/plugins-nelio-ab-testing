<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the Easy Digital Downloads order conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/edd-order'] = array(
		'name'               => 'nab/edd-order',
		'category'           => 'ecommerce',
		'title'              => _x( 'Easy Digital Downloads Purchase', 'text', 'nelio-ab-testing' ),
		'description'        => _x( 'Counts an Easy Digital Downloads purchase matching a download selection as a conversion.', 'text', 'nelio-ab-testing' ),
		'isPurchaseAction'   => true,
		'ecommercePlugin'    => 'edd',
		'requiresUniformity' => true,
		'defaults'           => array(
			'type'  => 'download-selection',
			'value' => array( 'type' => 'all-downloads' ),
		),
	);
	return $types;
}
