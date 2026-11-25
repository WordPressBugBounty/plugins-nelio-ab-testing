<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Time;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the time period segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/time'] = array(
		'name'        => 'nab/time',
		'title'       => _x( 'Time Period', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to their local time period.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/timestamp',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'values'    => array(),
		),
		'options'     => array(
			'00-02' => _x( '12a - 2a', 'text', 'nelio-ab-testing' ),
			'02-04' => _x( '2a - 4a', 'text', 'nelio-ab-testing' ),
			'04-06' => _x( '4a - 6a', 'text', 'nelio-ab-testing' ),
			'06-08' => _x( '6a - 8a', 'text', 'nelio-ab-testing' ),
			'08-10' => _x( '8a - 10a', 'text', 'nelio-ab-testing' ),
			'10-12' => _x( '10a - 12p', 'text', 'nelio-ab-testing' ),
			'12-14' => _x( '12p - 2p', 'text', 'nelio-ab-testing' ),
			'14-16' => _x( '2p - 4p', 'text', 'nelio-ab-testing' ),
			'16-18' => _x( '4p - 6p', 'text', 'nelio-ab-testing' ),
			'18-20' => _x( '6p - 8p', 'text', 'nelio-ab-testing' ),
			'20-22' => _x( '8p - 10p', 'text', 'nelio-ab-testing' ),
			'22-24' => _x( '10p - 12a', 'text', 'nelio-ab-testing' ),
		),
	);
	return $types;
}
