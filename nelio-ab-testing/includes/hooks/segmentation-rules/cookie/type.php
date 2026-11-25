<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Cookie;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the cookie segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/cookie'] = array(
		'name'        => 'nab/cookie',
		'title'       => _x( 'Cookie', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to a browser cookie.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/others',
		'singleton'   => false,
		'defaults'    => array(
			'name'      => '',
			'condition' => 'is-equal-to',
			'value'     => '',
		),
	);
	return $types;
}
