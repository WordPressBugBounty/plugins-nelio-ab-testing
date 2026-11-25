<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Time_On_Page;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the time-on-page conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/time-on-page'] = array(
		'name'                => 'nab/time-on-page',
		'category'            => 'other',
		'title'               => _x( 'Time on Page', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts a conversion after a visitor remains on a page for a selected amount of time.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array( 'value' => 20 ),
		'supportsCustomScope' => true,
	);
	return $types;
}
