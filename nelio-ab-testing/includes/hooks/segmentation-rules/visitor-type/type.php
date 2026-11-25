<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Visitor_Type;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the visitor type segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	if ( nab_is_visitor_type_script_disabled() ) {
		return $types;
	}

	$types['nab/visitor-type'] = array(
		'name'        => 'nab/visitor-type',
		'title'       => _x( 'Visitor Type', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to whether they are new or returning visitors.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/visitor',
		'singleton'   => true,
		'defaults'    => array( 'condition' => 'is-new-visitor' ),
	);
	return $types;
}
