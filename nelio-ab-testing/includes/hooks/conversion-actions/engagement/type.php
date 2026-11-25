<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Engagement;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the engagement conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/engagement'] = array(
		'name'                => 'nab/engagement',
		'category'            => 'interaction',
		'title'               => _x( 'Engagement', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts a conversion when the visitor performs a meaningful interaction with the active page, such as clicking certain elements.', 'text', 'nelio-ab-testing' ),
		'requiresSingleness'  => true,
		'defaults'            => array(),
		'supportsCustomScope' => true,
	);
	return $types;
}
