<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Element_View;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the element-view conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/element-view'] = array(
		'name'                         => 'nab/element-view',
		'category'                     => 'view',
		'title'                        => _x( 'Element view', 'text', 'nelio-ab-testing' ),
		'description'                  => _x( 'Counts a conversion when a selected element becomes visible in the visitor’s viewport.', 'text', 'nelio-ab-testing' ),
		'defaults'                     => array(
			'mode'  => 'css',
			'value' => '',
		),
		'supportsCustomScope'          => true,
		'canBeUsedForPageViewTracking' => true,
	);
	return $types;
}
