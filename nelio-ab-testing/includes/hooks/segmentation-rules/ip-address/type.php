<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Ip_Address;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the ip adress segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/ip-address'] = array(
		'name'        => 'nab/ip-address',
		'title'       => _x( 'IP Adress', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to their IP address.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/others',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'values'    => array(),
		),
	);
	return $types;
}
