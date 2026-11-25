<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Query_Parameter;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the query parameter segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/query-parameter'] = array(
		'name'        => 'nab/query-parameter',
		'title'       => _x( 'Query Parameter', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to a query parameter in the URL.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/source',
		'singleton'   => false,
		'defaults'    => array(
			'name'      => '',
			'condition' => 'is-equal-to',
			'value'     => '',
		),
	);
	return $types;
}
