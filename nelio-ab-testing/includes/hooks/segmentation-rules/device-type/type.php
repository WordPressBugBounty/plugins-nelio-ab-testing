<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Device_Type;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the device type segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/device-type'] = array(
		'name'        => 'nab/device-type',
		'title'       => _x( 'Device Type', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to their device type.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/technology',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'values'    => array(),
		),
		'options'     => array(
			'desktop' => _x( 'Desktop', 'text', 'nelio-ab-testing' ),
			'mobile'  => _x( 'Mobile', 'text', 'nelio-ab-testing' ),
			'tablet'  => _x( 'Tablet', 'text', 'nelio-ab-testing' ),
			'smarttv' => _x( 'Smart TV', 'text', 'nelio-ab-testing' ),
		),
	);
	return $types;
}
