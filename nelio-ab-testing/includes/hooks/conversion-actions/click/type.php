<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Click;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the click conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/click'] = array(
		'name'                => 'nab/click',
		'category'            => 'interaction',
		'title'               => _x( 'Click', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts a click on a selected page element as a conversion.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array(
			'mode'  => 'css',
			'value' => '',
		),
		'supportsCustomScope' => true,
	);
	return $types;
}
