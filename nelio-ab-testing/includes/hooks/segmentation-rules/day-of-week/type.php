<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Day_Of_Week;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the day of week segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/day-of-week'] = array(
		'name'        => 'nab/day-of-week',
		'title'       => _x( 'Day of Week', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to the day of the week in their local time.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/timestamp',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'values'    => array(),
		),
		'options'     => array(
			'Monday'    => _x( 'Monday', 'text', 'nelio-ab-testing' ),
			'Tuesday'   => _x( 'Tuesday', 'text', 'nelio-ab-testing' ),
			'Wednesday' => _x( 'Wednesday', 'text', 'nelio-ab-testing' ),
			'Thursday'  => _x( 'Thursday', 'text', 'nelio-ab-testing' ),
			'Friday'    => _x( 'Friday', 'text', 'nelio-ab-testing' ),
			'Saturday'  => _x( 'Saturday', 'text', 'nelio-ab-testing' ),
			'Sunday'    => _x( 'Sunday', 'text', 'nelio-ab-testing' ),
		),
	);
	return $types;
}
