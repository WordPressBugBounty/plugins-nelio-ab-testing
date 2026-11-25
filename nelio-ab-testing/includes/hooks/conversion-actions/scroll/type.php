<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Scroll;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the scroll conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/scroll'] = array(
		'name'                => 'nab/scroll',
		'category'            => 'interaction',
		'title'               => _x( 'Scroll', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts a conversion when a visitor reaches a selected scroll depth.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array( 'value' => 50 ),
		'supportsCustomScope' => true,
	);
	return $types;
}
