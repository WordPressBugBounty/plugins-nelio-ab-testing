<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Browser;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the browser segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/browser'] = array(
		'name'        => 'nab/browser',
		'title'       => _x( 'Browser', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to the browser they use.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/technology',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'values'    => array(),
		),
		'options'     => array(
			'Opera Mini'      => 'Opera Mini',
			'Opera Mobi'      => 'Opera Mobi',
			'Samsung Browser' => _x( 'Samsung Browser', 'text', 'nelio-ab-testing' ),
			'Chrome'          => 'Chrome',
			'Chromium'        => 'Chromium',
			'Edge'            => 'Edge',
			'Firefox'         => 'Firefox',
			'Mozilla'         => 'Mozilla',
			'Opera'           => 'Opera',
			'Safari'          => 'Safari',
			'Vivaldi'         => 'Vivaldi',
		),
	);
	return $types;
}
