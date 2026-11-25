<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Click_External_Link;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the external-link click conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/click-external-link'] = array(
		'name'                => 'nab/click-external-link',
		'category'            => 'interaction',
		'title'               => _x( 'Click external link', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts a click on an external link as a conversion.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array(
			'mode'  => 'exact',
			'value' => '',
		),
		'supportsCustomScope' => true,
	);
	return $types;
}
